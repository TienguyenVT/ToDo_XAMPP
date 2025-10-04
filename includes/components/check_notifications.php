<?php
// Tắt error reporting để tránh HTML error trong JSON response
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header trước khi có bất kỳ output nào
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/db_connect.php';

// Log để debug
error_log('Check notifications running at: ' . date('Y-m-d H:i:s'));

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Log để debug
    error_log("User ID for notifications: " . $user_id);
    
    // Lấy các notifications đến hạn
    $query = "
        SELECT nq.*, t.title as task_title, t.priority
        FROM notification_queue nq
        JOIN tasks t ON nq.task_id = t.id
        WHERE nq.user_id = ? 
        AND nq.scheduled_at <= NOW()
    ";
    
    // Log debug info
    error_log("DEBUG - Current time: " . date('Y-m-d H:i:s'));
    error_log("DEBUG - Query: " . str_replace('?', $user_id, $query));
    
    // Kiểm tra notifications trực tiếp
    $debug_query = "SELECT COUNT(*) as total FROM notification_queue WHERE user_id = $user_id";
    $debug_result = $conn->query($debug_query);
    $debug_count = $debug_result->fetch_assoc();
    error_log("DEBUG - Total notifications in queue: " . $debug_count['total']);

    // Kiểm tra reminders
    $debug_reminders = "SELECT COUNT(*) as total FROM reminders WHERE user_id = $user_id";
    $debug_rem_result = $conn->query($debug_reminders);
    $debug_rem_count = $debug_rem_result->fetch_assoc();
    error_log("DEBUG - Total reminders: " . $debug_rem_count['total']);
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $notifications = array();
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    // Chuẩn bị response (đọc-only, không cập nhật trạng thái ở đây)
    $response = [
        'success' => true,
        'notifications' => array_map(function($notif) {
            return [
                'id' => $notif['id'],
                'task_id' => $notif['task_id'],
                'reminder_id' => $notif['reminder_id'],
                'reminder_time' => $notif['scheduled_at'],
                'message' => $notif['message'],
                'type' => mapPriorityToType($notif['priority']),
                'title' => $notif['task_title']
            ];
        }, $notifications)
    ];

    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Error in check_notifications: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}

// Helper function to map priority to notification type
function mapPriorityToType($priority) {
    switch(strtolower((string)$priority)) {
        case 'high':
            return 'danger';
        case 'medium':
            return 'warning';
        default:
            return 'info';
    }
}