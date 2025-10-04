<?php

/**
 * Tạo nhắc nhở cho công việc và thêm vào notification queue
 */
function create_reminder($conn, $task_id, $reminder_time)
{
    $stmt = null;
    $transactionStarted = false;
    try {
        error_log("Starting create_reminder - Task ID: $task_id, Time: $reminder_time");
        
        // Validate input
        if (empty($task_id) || empty($reminder_time)) {
            throw new Exception("Task ID và thời gian nhắc nhở không được để trống");
        }
        
        // Bắt đầu transaction
        if (!$conn->begin_transaction()) {
            throw new Exception("Không thể bắt đầu transaction");
        }
        $transactionStarted = true;
        error_log("Transaction started");

        // 1. Lấy thông tin task
        $sql = "SELECT t.id, t.user_id, t.title as task_title 
                FROM tasks t 
                WHERE t.id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $task_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Không thể lấy thông tin task");
        }
        
        $result = $stmt->get_result();
        $task = $result->fetch_assoc();
    $stmt->close();
    $stmt = null;
        
        if (!$task) {
            throw new Exception("Không tìm thấy task");
        }
        
        // 2. Thêm reminder
        $sql = "INSERT INTO reminders (task_id, user_id, reminder_time) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $task_id, $task['user_id'], $reminder_time);
        
        if (!$stmt->execute()) {
            throw new Exception("Không thể tạo reminder");
        }
        
        $reminder_id = $stmt->insert_id;
    $stmt->close();
    $stmt = null;

        // 3. Tạo message cho notification
        $formatted_time = date('H:i d/m/Y', strtotime($reminder_time));
        $message = sprintf(
            'Nhắc nhở: Công việc "%s" đến hạn lúc %s',
            $task['task_title'],
            $formatted_time
        );

        // 4. Thêm vào notification queue
        $sql = "INSERT INTO notification_queue 
                (user_id, task_id, reminder_id, message, scheduled_at) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiss", 
            $task['user_id'], 
            $task_id, 
            $reminder_id, 
            $message, 
            $reminder_time);
            
        if (!$stmt->execute()) {
            throw new Exception("Không thể thêm vào notification queue");
        }
        
    $stmt->close();
    $stmt = null;

        // Commit transaction nếu mọi thứ OK
        $conn->commit();
        return true;

    } catch (Exception $e) {
        error_log("Error in create_reminder: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Rollback nếu có lỗi
        try {
            if ($transactionStarted) {
                $conn->rollback();
                error_log("Transaction rolled back successfully");
            }
        } catch (Exception $rollbackError) {
            error_log("Error during rollback: " . $rollbackError->getMessage());
        }
        
        throw $e;
    } finally {
        // Đảm bảo đóng statement nếu còn mở
        try {
            if ($stmt && $stmt instanceof mysqli_stmt) {
                // Only close if not already closed earlier
                $stmt->close();
                error_log("Statement closed successfully");
            }
        } catch (Exception $closeError) {
            error_log("Error closing statement: " . $closeError->getMessage());
        }
    }
}
/**
 * Lấy tất cả nhắc nhở của một công việc
 */
function get_reminders($conn, $task_id)
{
    $reminders = [];
    $sql = "SELECT * FROM reminders WHERE task_id = ? ORDER BY reminder_time ASC";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $task_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reminders[] = $row;
            }
        }
        $stmt->close();
    }
    return $reminders;
}

/**
 * Xóa nhắc nhở và notification tương ứng
 */
function delete_reminder($conn, $reminder_id)
{
    try {
        $conn->begin_transaction();

        // Xóa notifications trong queue trước
        $sql = "DELETE FROM notification_queue WHERE reminder_id = ? AND status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reminder_id);
        if (!$stmt->execute()) {
            throw new Exception("Không thể xóa notification");
        }
        $stmt->close();

        // Xóa reminder
        $sql = "DELETE FROM reminders WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $reminder_id);
        if (!$stmt->execute()) {
            throw new Exception("Không thể xóa reminder");
        }
        $stmt->close();

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in delete_reminder: " . $e->getMessage());
        return false;
    }
}

/**
 * Wrapper for compatibility: add_reminder calls create_reminder
 */
function add_reminder($conn, $task_id, $reminder_time)
{
    return create_reminder($conn, $task_id, $reminder_time);
}
