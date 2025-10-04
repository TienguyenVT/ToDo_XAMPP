<?php
require_once __DIR__ . '/../includes/db_connect.php';

// Kiểm tra và thêm reminder vào notification queue
function add_to_notification_queue($task_id, $reminder_time) {
    global $conn;
    
    try {
        // Lấy thông tin task và user
        $stmt = $conn->prepare("
            SELECT t.*, u.id as user_id 
            FROM tasks t
            JOIN users u ON t.user_id = u.id
            WHERE t.id = ?
        ");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task) {
            // Tạo notification message
            $message = sprintf(
                'Nhắc nhở: Công việc "%s" đến hạn lúc %s',
                $task['title'],
                date('H:i d/m/Y', strtotime($reminder_time))
            );
            
            // Thêm vào queue
            $stmt = $conn->prepare("
                INSERT INTO notification_queue 
                (user_id, task_id, reminder_id, message, scheduled_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $task['user_id'],
                $task_id,
                $reminder_id,
                $message,
                $reminder_time
            ]);
            
            return true;
        }
    } catch (PDOException $e) {
        error_log("Error adding to notification queue: " . $e->getMessage());
    }
    
    return false;
}

// Xóa notification khỏi queue khi xóa reminder
function remove_from_notification_queue($reminder_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            DELETE FROM notification_queue 
            WHERE reminder_id = ? 
            AND status = 'pending'
        ");
        $stmt->execute([$reminder_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Error removing from notification queue: " . $e->getMessage());
    }
    
    return false;
}