<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db_connect.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;

class EnhancedNotificationServer implements MessageComponentInterface {
    protected $clients;
    protected $loop;
    protected $conn;
    protected $userTabs = [];
    const INACTIVE_TIMEOUT = 120; // 2 minutes

    public function __construct($loop, $conn) {
        $this->clients = new \SplObjectStorage;
        $this->loop = $loop;
        $this->conn = $conn;
        
        // Thiết lập timer để check notifications mỗi 30 giây
        $this->loop->addPeriodicTimer(30, function () {
            $this->processNotificationQueue();
        });

        // Cleanup timer mỗi phút
        $this->loop->addPeriodicTimer(60, function () {
            $this->cleanupInactiveConnections();
        });
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        switch ($data['type']) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;
            case 'ping':
                $this->handlePing($from, $data);
                break;
            case 'disconnect':
                $this->handleDisconnect($from, $data);
                break;
        }
    }

    protected function handleAuth($from, $data) {
        $from->user_id = $data['user_id'];
        $from->tab_id = $data['tab_id'];
        
        // Lưu thông tin tab
        $this->userTabs[$data['user_id']][$data['tab_id']] = [
            'connection' => $from,
            'last_activity' => time(),
            'status' => 'active'
        ];
        
        // Cập nhật connection trong database
        $stmt = $this->conn->prepare("
            INSERT INTO user_connections 
            (user_id, connection_id, tab_id, status) 
            VALUES (?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE 
            last_ping = NOW(),
            status = 'active'
        ");
        $stmt->execute([
            $data['user_id'],
            $from->resourceId,
            $data['tab_id']
        ]);
    }

    protected function handlePing($from, $data) {
        if (isset($from->user_id) && isset($from->tab_id)) {
            // Cập nhật trạng thái tab
            if (isset($this->userTabs[$from->user_id][$from->tab_id])) {
                $this->userTabs[$from->user_id][$from->tab_id]['last_activity'] = time();
                $this->userTabs[$from->user_id][$from->tab_id]['status'] = 
                    $data['status'] ?? 'active';
            }

            // Cập nhật trong database
            $stmt = $this->conn->prepare("
                UPDATE user_connections 
                SET last_ping = NOW(),
                    status = ?
                WHERE user_id = ? 
                AND connection_id = ?
                AND tab_id = ?
            ");
            $stmt->execute([
                $data['status'] ?? 'active',
                $from->user_id,
                $from->resourceId,
                $from->tab_id
            ]);

            // Gửi pong response
            $from->send(json_encode(['type' => 'pong']));
        }
    }

    protected function handleDisconnect($from, $data) {
        if (isset($data['user_id']) && isset($data['tab_id'])) {
            // Xóa thông tin tab
            unset($this->userTabs[$data['user_id']][$data['tab_id']]);
            if (empty($this->userTabs[$data['user_id']])) {
                unset($this->userTabs[$data['user_id']]);
            }

            // Xóa connection từ database
            $stmt = $this->conn->prepare("
                DELETE FROM user_connections 
                WHERE user_id = ? 
                AND tab_id = ?
            ");
            $stmt->execute([$data['user_id'], $data['tab_id']]);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        if (isset($conn->user_id) && isset($conn->tab_id)) {
            $this->handleDisconnect($conn, [
                'user_id' => $conn->user_id,
                'tab_id' => $conn->tab_id
            ]);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function cleanupInactiveConnections() {
        // Cleanup memory
        foreach ($this->userTabs as $userId => $tabs) {
            foreach ($tabs as $tabId => $tabInfo) {
                if (time() - $tabInfo['last_activity'] > self::INACTIVE_TIMEOUT) {
                    $this->handleDisconnect($tabInfo['connection'], [
                        'user_id' => $userId,
                        'tab_id' => $tabId
                    ]);
                }
            }
        }

        // Cleanup database
        $stmt = $this->conn->prepare("
            DELETE FROM user_connections 
            WHERE last_ping < NOW() - INTERVAL ? SECOND
        ");
        $stmt->execute([self::INACTIVE_TIMEOUT]);
    }

    protected function processNotificationQueue() {
        // Lấy các notification đến hạn
        $stmt = $this->conn->prepare("
            SELECT nq.*, t.title as task_title, u.id as user_id 
            FROM notification_queue nq
            JOIN tasks t ON nq.task_id = t.id
            JOIN users u ON nq.user_id = u.id
            WHERE nq.status = 'pending' 
            AND nq.scheduled_at <= NOW()
            LIMIT 50
        ");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($notifications as $notification) {
            // Đánh dấu đang xử lý
            $stmt = $this->conn->prepare("
                UPDATE notification_queue 
                SET status = 'processing',
                    processed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$notification['id']]);

            $notificationSent = false;

            // Gửi notification đến tất cả các tab active của user
            if (isset($this->userTabs[$notification['user_id']])) {
                foreach ($this->userTabs[$notification['user_id']] as $tabInfo) {
                    if ($tabInfo['status'] === 'active') {
                        $tabInfo['connection']->send(json_encode([
                            'type' => 'reminder',
                            'title' => $notification['task_title'],
                            'message' => $notification['message'],
                            'task_id' => $notification['task_id']
                        ]));
                        $notificationSent = true;
                    }
                }
            }

            // Cập nhật trạng thái
            $newStatus = $notificationSent ? 'sent' : 'failed';
            $stmt = $this->conn->prepare("
                UPDATE notification_queue 
                SET status = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newStatus, $notification['id']]);
        }
    }
}

// Khởi tạo event loop
$loop = Factory::create();
$server = new EnhancedNotificationServer($loop, $conn);

// Tạo WebSocket server
$webSocket = new IoServer(
    new HttpServer(
        new WsServer($server)
    ),
    8080,
    '0.0.0.0',
    $loop
);

echo "Enhanced Notification Server started on port 8080\n";
$loop->run();