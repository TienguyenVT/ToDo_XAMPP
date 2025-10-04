<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/db_connect.php';

// Log để debug
error_log('Check notifications running at: ' . date('Y-m-d H:i:s'));

header('Content-Type: application/json');

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Lấy các notifications đến hạn, bao gồm cả những notification trong 30 giây trước
    $query = "
        SELECT nq.*, t.title as task_title, t.priority
        FROM notification_queue nq
        JOIN tasks t ON nq.task_id = t.id
        WHERE nq.user_id = ? 
        AND nq.status = 'pending'
        AND nq.scheduled_at <= NOW()
    ";
    
    error_log("Query: " . str_replace('?', $user_id, $query));
    
    $stmt = $conn->prepare($query);
    
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cập nhật trạng thái các notification đã gửi
    if (!empty($notifications)) {
        $notify_ids = array_column($notifications, 'id');
        $placeholders = str_repeat('?,', count($notify_ids) - 1) . '?';
        
        $update = $conn->prepare("
            UPDATE notification_queue 
            SET status = 'sent' 
            WHERE id IN ($placeholders)
        ");
        
        $update->execute($notify_ids);
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}