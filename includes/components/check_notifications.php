<?php
// Tắt error reporting để tránh HTML error trong JSON response
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header trước khi có bất kỳ output nào
header('Content-Type: application/json');

// Ensure shared helpers are loaded
require_once dirname(__DIR__) . '/functions.php';
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
    
    // Lấy các reminders đến hạn trực tiếp từ bảng reminders
    $query = "
        SELECT r.id as reminder_id, r.task_id, r.reminder_time, t.title as task_title, t.priority
        FROM reminders r
        JOIN tasks t ON r.task_id = t.id
        WHERE r.user_id = ?
        AND r.reminder_time <= NOW()
        AND r.notified_at IS NULL
    ";

    // Log debug info
    error_log("DEBUG - Current time: " . date('Y-m-d H:i:s'));
    
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
    $reminderIds = array();

    while ($row = $result->fetch_assoc()) {
        $message = function_exists('format_notification_message') ? format_notification_message($row['task_title'], $row['reminder_time']) : '';
        $notifications[] = [
            'id' => (int)$row['reminder_id'],
            'task_id' => (int)$row['task_id'],
            'reminder_id' => (int)$row['reminder_id'],
            'reminder_time' => $row['reminder_time'],
            'message' => $message,
            'type' => mapPriorityToType($row['priority']),
            'title' => $row['task_title']
        ];
        $reminderIds[] = (int)$row['reminder_id'];
    }

    // Đánh dấu đã thông báo (chỉ khi có notifications)
    if (!empty($reminderIds)) {
        $ids = implode(',', array_map('intval', $reminderIds));
        $updateSql = "UPDATE reminders SET notified_at = NOW() WHERE id IN ($ids) AND notified_at IS NULL";
        $conn->query($updateSql);
    }

    $response = [ 'success' => true, 'notifications' => $notifications ];
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