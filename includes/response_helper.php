<?php
/**
 * Hàm helper để gửi JSON response
 */
function send_json_response($data, $status_code = 200) {
    // Đảm bảo không có output nào trước đó
    if (ob_get_length()) ob_clean();
    
    // Set headers
    header('Content-Type: application/json');
    http_response_code($status_code);
    
    // Log response for debugging
    error_log("Sending response: " . json_encode($data));
    
    // Send JSON response
    echo json_encode($data);
    exit();
}

/**
 * Hàm helper để gửi JSON error response
 */
function send_json_error($message, $status_code = 400) {
    send_json_response([
        'success' => false,
        'message' => $message
    ], $status_code);
}

/**
 * Hàm helper để gửi JSON success response
 */
function send_json_success($data = [], $message = '') {
    $response = ['success' => true];
    if (!empty($message)) {
        $response['message'] = $message;
    }
    if (!empty($data)) {
        $response['data'] = $data;
    }
    send_json_response($response, 200);
}