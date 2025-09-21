<?php
session_start();
require_once 'includes/db_connect.php';

$username = $password = $full_name = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form và làm sạch
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);

    // --- Validation ---
    if (empty($username)) {
        $errors[] = "Tên đăng nhập là bắt buộc.";
    }
    if (empty($password)) {
        $errors[] = "Mật khẩu là bắt buộc.";
    }
    if (empty($full_name)) {
        $errors[] = "Họ và tên là bắt buộc.";
    }

    // Kiểm tra xem username đã tồn tại chưa
    if (empty($errors)) {
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $errors[] = "Tên đăng nhập này đã tồn tại.";
                }
            } else {
                $errors[] = "Đã có lỗi xảy ra. Vui lòng thử lại sau.";
            }
            $stmt->close();
        }
    }

    // Nếu không có lỗi, tiến hành chèn vào CSDL
    if (empty($errors)) {
        $sql = "INSERT INTO users (username, password, full_name) VALUES (?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            // Mã hóa mật khẩu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bind_param("sss", $username, $hashed_password, $full_name);

            if ($stmt->execute()) {
                // Đăng ký thành công, chuyển hướng đến trang đăng nhập
                header("location: login.php?registration_success=1");
                exit();
            } else {
                $errors[] = "Đã có lỗi xảy ra khi tạo tài khoản.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - TodoWeb</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card mt-5">
                    <div class="card-body">
                        <h3 class="card-title text-center mb-4">Tạo Tài Khoản Mới</h3>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-0"><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Họ và Tên</label>
                                <input type="text" name="full_name" id="full_name" class="form-control" value="<?php echo htmlspecialchars($full_name); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Tên Đăng Nhập</label>
                                <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mật Khẩu</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Đăng Ký</button>
                            </div>
                            <p class="text-center mt-3">
                                Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a>.
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
