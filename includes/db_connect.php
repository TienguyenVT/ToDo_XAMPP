<?php
// Thông tin kết nối CSDL
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Mặc định của XAMPP
define('DB_PASSWORD', '');     // Mặc định của XAMPP
define('DB_NAME', 'todoweb_db');

// Tạo kết nối đến MySQL
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập charset thành UTF-8 để hỗ trợ tiếng Việt
$conn->set_charset("utf8");
?>
