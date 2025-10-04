<?php
/**
 * Temporary debug endpoint to call create_reminder directly.
 * Usage (browser):
 *  http://localhost/ToDo/debug_create_reminder.php?task_id=3&time=2025-10-04%2020:30:00
 * This file is for local debugging only and will be removed after use.
 */

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/components/reminders.php';
require_once __DIR__ . '/includes/response_helper.php';

header('Content-Type: application/json');

$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : null;
$time = isset($_GET['time']) ? $_GET['time'] : null;

if (!$task_id || !$time) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing task_id or time']);
    exit;
}

try {
    $ok = create_reminder($conn, $task_id, $time);
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Reminder created (debug)']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'create_reminder returned false']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}

exit;
