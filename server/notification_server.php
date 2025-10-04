<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db_connect.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;

class NotificationServer implements MessageComponentInterface {
    protected $clients;
    protected $loop;
    protected $conn;

    public function __construct($loop, $conn) {
        $this->clients = new \SplObjectStorage;
        $this->loop = $loop;
        $this->conn = $conn;
        
        // Thiết lập timer để check notifications mỗi 30 giây
        $this->loop->addPeriodicTimer(30, function () {
            $this->processNotificationQueue();
        });
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if ($data['type'] === 'auth') {
            // Lưu user_id vào connection
            $from->user_id = $data['user_id'];
            
            // Cập nhật connection trong database
            $stmt = $this->conn->prepare("INSERT INTO user_connections (user_id, connection_id) VALUES (?, ?)");
            $stmt->execute([$data['user_id'], $from->resourceId]);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Xóa connection khỏi database
        if (isset($conn->user_id)) {
            $stmt = $this->conn->prepare("DELETE FROM user_connections WHERE user_id = ? AND connection_id = ?");
            $stmt->execute([$conn->user_id, $conn->resourceId]);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
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
                SET status = 'processing', processed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$notification['id']]);

            // Gửi notification đến user
            foreach ($this->clients as $client) {
                if (isset($client->user_id) && $client->user_id == $notification['user_id']) {
                    $client->send(json_encode([
                        'type' => 'reminder',
                        'title' => $notification['task_title'],
                        'message' => $notification['message'],
                        'task_id' => $notification['task_id'],
                        'reminder_id' => $notification['reminder_id'] ?? null,
                        'reminder_time' => $notification['scheduled_at'] ?? null
                    ]));
                }
            }

            // Đánh dấu đã gửi
            $stmt = $this->conn->prepare("
                UPDATE notification_queue 
                SET status = 'sent' 
                WHERE id = ?
            ");
            $stmt->execute([$notification['id']]);
        }
    }
}

// Khởi tạo event loop
$loop = Factory::create();
$server = new NotificationServer($loop, $conn);

// Tạo WebSocket server
$webSocket = new IoServer(
    new HttpServer(
        new WsServer($server)
    ),
    8080,
    '0.0.0.0',
    $loop
);

echo "Notification Server started on port 8080\n";
$loop->run();