<?php
require_once __DIR__ . '/../db_connect.php';

// Kiểm tra và thêm reminder vào notification queue (mysqli style, uses $conn)
function add_to_notification_queue($task_id, $reminder_time, $reminder_id) {
    global $conn;
    
    try {
        // Lấy thông tin task và user
        $stmt = $conn->prepare("SELECT t.*, u.id as user_id FROM tasks t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
        if (!$stmt) return false;
        $stmt->bind_param('i', $task_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $task = $res->fetch_assoc();
        $stmt->close();
        
        if ($task) {
            // Tạo notification message
            $message = sprintf(
                'Nhắc nhở: Công việc "%s" đến hạn lúc %s',
                $task['title'],
                date('H:i d/m/Y', strtotime($reminder_time))
            );
            
            // Thêm vào queue
            $stmt = $conn->prepare("INSERT INTO notification_queue (user_id, task_id, reminder_id, message, scheduled_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('iiiss', $task['user_id'], $task_id, $reminder_id, $message, $reminder_time);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok;
        }
    } catch (Exception $e) {
        error_log("Error adding to notification queue: " . $e->getMessage());
    }
    
    return false;
}

// Xóa notification khỏi queue khi xóa reminder
function remove_from_notification_queue($reminder_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("DELETE FROM notification_queue WHERE reminder_id = ?");
        if (!$stmt) return false;
        $stmt->bind_param('i', $reminder_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    } catch (Exception $e) {
        error_log("Error removing from notification queue: " . $e->getMessage());
    }
    
    return false;
}