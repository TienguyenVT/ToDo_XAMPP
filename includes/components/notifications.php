<?php
// includes/components/notifications.php
// Trả về các nhắc nhở đến hạn cho user hiện tại (trong 5 phút tới)

session_start();
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/reminders.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');
$next5min = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Lấy tất cả task của user
$sql = "SELECT id, title FROM tasks WHERE user_id = ?";
$tasks = [];
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    $stmt->close();
}

$notifications = [];
foreach ($tasks as $task) {
    $reminders = get_reminders($conn, $task['id']);
    foreach ($reminders as $rem) {
        if ($rem['reminder_time'] >= $now && $rem['reminder_time'] <= $next5min) {
            $notifications[] = [
                'task_id' => $task['id'],
                'task_title' => $task['title'],
                'reminder_time' => $rem['reminder_time']
            ];
        }
    }
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
