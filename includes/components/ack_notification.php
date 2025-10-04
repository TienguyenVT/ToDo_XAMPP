<?php
// ack_notification.php
// Marks a notification as acknowledged/sent when the client displays it.
header('Content-Type: application/json');
require_once __DIR__ . '/../db_connect.php';
session_start();

// Only accept JSON POST
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing id']);
    exit;
}

$notification_id = intval($data['id']);

try {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }

    $user_id = intval($_SESSION['user_id']);

    // Verify ownership and update status to 'sent' or 'acknowledged'
    $stmt = $conn->prepare("SELECT id, user_id, status FROM notification_queue WHERE id = ? LIMIT 1");
    $stmt->execute([$notification_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
        exit;
    }

    if (intval($row['user_id']) !== $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    // Update status to sent/acknowledged if it's still pending or processing
    $update = $conn->prepare("UPDATE notification_queue SET status = 'sent', acknowledged_at = NOW() WHERE id = ? AND user_id = ? AND status != 'sent'");
    $update->execute([$notification_id, $user_id]);

    echo json_encode(['success' => true]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    error_log('ack_notification error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
