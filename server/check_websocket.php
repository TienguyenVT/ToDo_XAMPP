<?php
// Kiểm tra kết nối WebSocket
$errno = null;
$errstr = null;
$socket = @fsockopen('127.0.0.1', 8080, $errno, $errstr, 1);

if (!$socket) {
    echo "WebSocket server không hoạt động\n";
    echo "Error: $errstr ($errno)\n";
} else {
    echo "WebSocket server đang chạy\n";
    fclose($socket);
}
?>