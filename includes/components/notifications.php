<?php
// includes/components/notifications.php
// Trả về các nhắc nhở đến hạn cho user hiện tại (trong 5 phút tới)

session_start();
// Ensure shared helpers (including format_notification_message) are available
require_once dirname(__DIR__) . '/functions.php';
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');
$next5min = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Lấy reminders sắp đến trong 5 phút tới thay vì dùng notification_queue

$query = "
        SELECT r.id as reminder_id, r.task_id, r.reminder_time, t.title as task_title, t.priority
        FROM reminders r
        JOIN tasks t ON r.task_id = t.id
        WHERE r.user_id = ?
            AND r.reminder_time BETWEEN ? AND ?
            AND r.notified_at IS NULL
        ORDER BY r.reminder_time ASC
";

$notifications = [];
$reminderIds = [];
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param('iss', $user_id, $now, $next5min);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $message = function_exists('format_notification_message') ? format_notification_message($row['task_title'], $row['reminder_time']) : '';
        $notifications[] = [
            'id' => (int)$row['reminder_id'],
            'task_id' => (int)$row['task_id'],
            'reminder_id' => (int)$row['reminder_id'],
            'task_title' => $row['task_title'],
            'message' => $message,
            'reminder_time' => $row['reminder_time'],
            'priority' => $row['priority'] ?? null,
        ];
        $reminderIds[] = (int)$row['reminder_id'];
    }
    $stmt->close();
}

// Đánh dấu đã thông báo (chỉ khi có notifications)
if (!empty($reminderIds)) {
    $ids = implode(',', array_map('intval', $reminderIds));
    $updateSql = "UPDATE reminders SET notified_at = NOW() WHERE id IN ($ids) AND notified_at IS NULL";
    $conn->query($updateSql);
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
