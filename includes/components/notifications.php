<?php
// includes/components/notifications.php
// Trả về các nhắc nhở đến hạn cho user hiện tại (trong 5 phút tới)

session_start();
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');
$next5min = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Lấy notification_queue entries pending cho user trong khoảng 5 phút tới
$query = "
    SELECT nq.id, nq.task_id, nq.reminder_id, nq.message, nq.scheduled_at, t.title as task_title, t.priority
    FROM notification_queue nq
    JOIN tasks t ON nq.task_id = t.id
        WHERE nq.user_id = ?
            AND nq.scheduled_at BETWEEN ? AND ?
    ORDER BY nq.scheduled_at ASC
";

$notifications = [];
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param('iss', $user_id, $now, $next5min);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => (int)$row['id'],
            'task_id' => (int)$row['task_id'],
            'reminder_id' => (int)$row['reminder_id'],
            'task_title' => $row['task_title'],
            'message' => $row['message'],
            'reminder_time' => $row['scheduled_at'],
            'priority' => $row['priority'] ?? null,
        ];
    }
    $stmt->close();
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
