<?php
/**
 * Lấy tất cả công việc của một người dùng cụ thể
 * @param mysqli $conn Đối tượng kết nối CSDL
 * @param int $user_id ID của người dùng
 * @return array Mảng chứa các công việc
 */
function get_user_tasks($conn, $user_id, $priority = null) {
    $tasks = [];
    $valid_priorities = ['low', 'medium', 'high'];
    if ($priority !== null && !in_array($priority, $valid_priorities)) {
        // invalid priority filter, return empty result to be safe
        return $tasks;
    }

    if ($priority === null) {
        $sql = "SELECT id, title, description, status, due_date, created_at, priority FROM tasks WHERE user_id = ? ORDER BY created_at DESC";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $tasks[] = $row;
                }
            }
            $stmt->close();
        }
    } else {
        $sql = "SELECT id, title, description, status, due_date, created_at, priority FROM tasks WHERE user_id = ? AND priority = ? ORDER BY created_at DESC";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("is", $user_id, $priority);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $tasks[] = $row;
                }
            }
            $stmt->close();
        }
    }
    return $tasks;
}

/**
 * Tạo một công việc mới
 * @param mysqli $conn Đối tượng kết nối CSDL
 * @param int $user_id ID của người dùng
 * @param string $title Tiêu đề công việc
 * @param string $description Mô tả công việc
 * @param string|null $due_date Ngày hết hạn
 * @return bool True nếu thành công, False nếu thất bại
 */
function create_task($conn, $user_id, $title, $description, $due_date, $priority) {
    $sql = "INSERT INTO tasks (user_id, title, description, due_date, priority) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("issss", $user_id, $title, $description, $due_date, $priority);
        if ($stmt->execute()) {
            return true;
        }
        $stmt->close();
    }
    return false;
}

/**
 * Cập nhật trạng thái của một công việc
 * @param mysqli $conn Đối tượng kết nối CSDL
 * @param int $task_id ID của công việc
 * @param string $status Trạng thái mới ('pending' hoặc 'completed')
 * @param int $user_id ID của người dùng (để bảo mật)
 * @return bool True nếu thành công, False nếu thất bại
 */
function update_task_status($conn, $task_id, $status, $user_id) {
    if (!in_array($status, ['pending', 'in-progress', 'completed'])) {
        error_log("Invalid status attempted: " . $status);
        return false;
    }
    
    $sql = "UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sii", $status, $task_id, $user_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return true;
            } else {
                error_log("No rows updated for task_id: $task_id, user_id: $user_id, status: $status");
                return false;
            }
        }
        error_log("Error executing update_task_status: " . $stmt->error);
        $stmt->close();
    } else {
        error_log("Error preparing update_task_status: " . $conn->error);
    }
    return false;
}

/**
 * Xóa một công việc
 * @param mysqli $conn Đối tượng kết nối CSDL
 * @param int $task_id ID của công việc
 * @param int $user_id ID của người dùng (để bảo mật)
 * @return bool True nếu thành công, False nếu thất bại
 */
function delete_task($conn, $task_id, $user_id) {
    $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            return true;
        }
        $stmt->close();
    }
    return false;
}

/**
 * Lấy chi tiết một công việc
 */
function get_task_detail($conn, $task_id, $user_id) {
    $sql = "SELECT id, title, description, due_date, status, priority FROM tasks WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $task_id, $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        }
        $stmt->close();
    }
    return null;
}

/**
 * Cập nhật nội dung công việc
 */
function update_task($conn, $task_id, $user_id, $title, $description, $due_date, $priority) {
    $sql = "UPDATE tasks SET title = ?, description = ?, due_date = ?, priority = ? WHERE id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssii", $title, $description, $due_date, $priority, $task_id, $user_id);
        if ($stmt->execute()) {
            return true;
        }
        $stmt->close();
    }
    return false;
}