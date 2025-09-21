<?php
/**
 * Tạo nhắc nhở cho công việc
 */
function create_reminder($conn, $task_id, $reminder_time) {
    $sql = "INSERT INTO reminders (task_id, reminder_time) VALUES (?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $task_id, $reminder_time);
        if ($stmt->execute()) {
            return true;
        }
        $stmt->close();
    }
    return false;
}

/**
 * Lấy tất cả nhắc nhở của một công việc
 */
function get_reminders($conn, $task_id) {
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
 * Xóa nhắc nhở
 */
function delete_reminder($conn, $reminder_id) {
    $sql = "DELETE FROM reminders WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $reminder_id);
        if ($stmt->execute()) {
            return true;
        }
        $stmt->close();
    }
    return false;
}

/**
 * Wrapper for compatibility: add_reminder calls create_reminder.
 */
function add_reminder($conn, $task_id, $reminder_time) {
    return create_reminder($conn, $task_id, $reminder_time);
}