<?php
/**
 * Thống kê số lượng công việc theo trạng thái
 */
function get_task_count_by_status($conn, $user_id) {
    $result = [];
    $sql = "SELECT status, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY status";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $rs = $stmt->get_result();
            while ($row = $rs->fetch_assoc()) {
                $result[$row['status']] = $row['count'];
            }
        }
        $stmt->close();
    }
    return $result;
}


/**
 * Thống kê số lượng công việc theo ưu tiên
 */
function get_task_count_by_priority($conn, $user_id) {
    $result = [];
    $sql = "SELECT priority, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY priority";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $rs = $stmt->get_result();
            while ($row = $rs->fetch_assoc()) {
                $result[$row['priority']] = $row['count'];
            }
        }
        $stmt->close();
    }
    return $result;
}

/**
 * Thống kê tổng thời gian theo dõi cho từng công việc
 */
function get_total_time_by_task($conn, $task_id) {
    $sql = "SELECT SUM(duration) as total FROM time_sessions WHERE task_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $task_id);
        if ($stmt->execute()) {
            $rs = $stmt->get_result();
            $row = $rs->fetch_assoc();
            return intval($row['total']);
        }
        $stmt->close();
    }
    return 0;
}

/**
 * Thống kê tổng thời gian theo dõi cho toàn bộ công việc của người dùng
 */
function get_total_time_by_user($conn, $user_id) {
    $sql = "SELECT SUM(ts.duration) as total FROM time_sessions ts INNER JOIN tasks t ON ts.task_id = t.id WHERE t.user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $rs = $stmt->get_result();
            $row = $rs->fetch_assoc();
            return intval($row['total']);
        }
        $stmt->close();
    }
    return 0;
}