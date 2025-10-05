<?php
// Endpoint trả về danh sách tất cả nhắc nhở của user hiện tại
session_start();
require_once dirname(__DIR__) . '/db_connect.php';
require_once dirname(__DIR__) . '/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT r.id, r.task_id, r.reminder_time, r.notified_at, t.title as task_title, t.priority
        FROM reminders r
        JOIN tasks t ON r.task_id = t.id
        WHERE r.user_id = ?
        ORDER BY r.reminder_time DESC";

$reminders = [];
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reminders[] = [
            'id' => (int)$row['id'],
            'task_id' => (int)$row['task_id'],
            'task_title' => $row['task_title'],
            'reminder_time' => $row['reminder_time'],
            'notified_at' => $row['notified_at'],
            'priority' => $row['priority'] ?? null,
            'message' => function_exists('format_notification_message') ? format_notification_message($row['task_title'], $row['reminder_time']) : '',
        ];
    }
    $stmt->close();
}

echo json_encode(['success' => true, 'reminders' => $reminders]);
