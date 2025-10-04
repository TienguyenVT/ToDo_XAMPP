<?php
// Tắt hiển thị lỗi trên output
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', 'error.log');

session_start();

// Nếu người dùng chưa đăng nhập, chuyển hướng về trang đăng nhập
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/response_helper.php';

// Detect AJAX request (used to decide whether to output full HTML)
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Development debug flag - when true, AJAX errors will include exception traces in JSON
define('DEV_DEBUG', true);

// Convert PHP warnings/notices to ErrorException so they can be caught
set_error_handler(function ($severity, $message, $file, $line) {
    // Respect error_reporting level
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Uncaught exception handler - log and, for AJAX, return JSON error with optional trace
set_exception_handler(function ($e) use ($isAjaxRequest) {
    error_log("Uncaught exception: " . $e->getMessage());
    error_log($e->getTraceAsString());
    if ($isAjaxRequest) {
        if (defined('DEV_DEBUG') && DEV_DEBUG) {
            send_json_error($e->getMessage() . "\n" . $e->getTraceAsString(), 500);
        } else {
            send_json_error('Internal server error', 500);
        }
    } else {
        // For non-AJAX, just log and show a simple message
        http_response_code(500);
        echo '<h1>Internal Server Error</h1>';
    }
});

// Shutdown handler to catch fatal errors
register_shutdown_function(function () use ($isAjaxRequest) {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log('Shutdown fatal error: ' . print_r($err, true));
        if ($isAjaxRequest) {
            if (defined('DEV_DEBUG') && DEV_DEBUG) {
                send_json_error('Fatal error: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line'], 500);
            } else {
                send_json_error('Internal server error', 500);
            }
        }
    }
});

// Always include component files so their functions are available (including render_header)
require_once 'components/layout/header.php';
require_once 'components/layout/footer.php';
require_once 'components/tasks/task_form.php';
require_once 'components/tasks/kanban_board.php';
require_once 'components/statistics/statistics_board.php';

$user_id = $_SESSION['user_id'];
$tasks = [];
$edit_task = null;

// Log incoming request for debugging AJAX 500 errors
try {
    $reqInfo = [
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'isAjax' => $isAjaxRequest ? 'yes' : 'no',
        'headers' => function_exists('getallheaders') ? getallheaders() : [],
        'post' => $_POST,
        'raw_input' => file_get_contents('php://input')
    ];
    error_log('Request debug: ' . json_encode($reqInfo));
} catch (Throwable $t) {
    error_log('Failed to log request debug: ' . $t->getMessage());
}

// Xử lý thêm công việc mới
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = !empty($_POST['due_date']) ? trim($_POST['due_date']) : null;
    $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';

    if (!empty($title)) {
        create_task($conn, $user_id, $title, $description, $due_date, $priority);
        set_flash('Thêm công việc thành công!', 'success');
        header("location: index.php");
        exit();
    }
}

// Xử lý cập nhật trạng thái công việc
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['status'];
    update_task_status($conn, $task_id, $new_status, $user_id);
    set_flash('Cập nhật trạng thái thành công!', 'success');
    header("location: index.php");
    exit();
}

// Xử lý xóa công việc
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'];
    delete_task($conn, $task_id, $user_id);
    set_flash('Xóa công việc thành công!', 'success');
    header("location: index.php");
    exit();
}

// Xử lý sửa công việc
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_task = get_task_detail($conn, $edit_id, $user_id);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_task'])) {
    $task_id = intval($_POST['task_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = !empty($_POST['due_date']) ? trim($_POST['due_date']) : null;
    $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';

    update_task($conn, $task_id, $user_id, $title, $description, $due_date, $priority);
    set_flash('Cập nhật công việc thành công!', 'success');
    header("location: index.php");
    exit();
}

// Xử lý nhắc nhở
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_reminder'])) {
    try {
        if (!$isAjaxRequest) {
            send_json_error("Invalid request type", 400);
        }
        $task_id = intval($_POST['task_id']);
        $reminder_time = $_POST['reminder_time'];
        error_log("Processing reminder - Task: $task_id, Time: $reminder_time, User: $user_id");
        if (empty($task_id) || empty($reminder_time)) {
            send_json_error("Vui lòng điền đầy đủ thông tin");
        }
        // Kiểm tra quyền truy cập task
        $sql = "SELECT id FROM tasks WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            send_json_error("Database error", 500);
        }
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            $stmt->close();
            send_json_error("Không có quyền thực hiện thao tác này", 403);
        }
        $stmt->close();
        // Validate reminder time
        $reminder_datetime = new DateTime($reminder_time);
        $current_datetime = new DateTime();
        if ($reminder_datetime < $current_datetime) {
            send_json_error("Thời gian nhắc nhở phải lớn hơn thời gian hiện tại");
        }
        // Tạo reminder với transaction
        $result = false;
        try {
            $result = create_reminder($conn, $task_id, $reminder_time);
        } catch (Exception $e) {
            error_log("Exception in create_reminder: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            send_json_error("Lỗi khi tạo nhắc nhở: " . $e->getMessage(), 500);
        }
        if (!$result) {
            send_json_error("Không thể tạo nhắc nhở", 500);
        }
        send_json_success([], 'Thêm nhắc nhở thành công!');
    } catch (Exception $e) {
        error_log("Reminder error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        send_json_error($e->getMessage(), 400);
    } catch (Throwable $t) {
        error_log("Unexpected error: " . $t->getMessage());
        error_log("Stack trace: " . $t->getTraceAsString());
        send_json_error("Có lỗi xảy ra, vui lòng thử lại sau", 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_reminder'])) {
    $reminder_id = intval($_POST['reminder_id']);
    delete_reminder($conn, $reminder_id);
    set_flash('Xóa nhắc nhở thành công!', 'success');
    header("location: index.php");
    exit();
}

// Lấy danh sách công việc của người dùng (hỗ trợ filter theo priority)
$priority_filter = isset($_GET['priority_filter']) && $_GET['priority_filter'] !== '' ? $_GET['priority_filter'] : null;
$tasks = get_user_tasks($conn, $user_id, $priority_filter);

// Lọc theo tìm kiếm nếu có
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $keyword = mb_strtolower(trim($_GET['search']));
    $tasks = array_filter($tasks, function ($task) use ($keyword) {
        $title = mb_strtolower($task['title']);
        $desc = mb_strtolower($task['description']);
        return strpos($title, $keyword) !== false
            || strpos($desc, $keyword) !== false;
    });
}

// Sắp xếp công việc nếu có
if (isset($_GET['sort']) && $_GET['sort'] !== '') {
    $sort = $_GET['sort'];
    usort($tasks, function ($a, $b) use ($sort) {
        if ($sort == 'due_date') {
            return strtotime($a['due_date']) <=> strtotime($b['due_date']);
        } elseif ($sort == 'status') {
            return strcmp($a['status'], $b['status']);
        }
        return 0;
    });
}

// Lấy danh sách danh mục và thống kê
$stats_status = get_task_count_by_status($conn, $user_id);
$stats_priority = get_task_count_by_priority($conn, $user_id);

// Render giao diện (chỉ khi không phải AJAX request)
if (!$isAjaxRequest) {
    // Render header
    render_header($_SESSION["full_name"]);

    // Hiển thị flash message nếu có (session-based)
    // display_flash() sẽ echo một <div class="alert alert-...">..</div>
    display_flash();
    ?>

    <div class="container mt-5">
        <!-- BÁO CÁO THỐNG KÊ -->
        <?php render_statistics($stats_status, $stats_priority); ?>

        <!-- KANBAN + FORM CRUD -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-3">
                                <h3><?php echo $edit_task ? 'Sửa Công Việc' : 'Thêm Công Việc'; ?></h3>
                                <?php render_task_form($edit_task); ?>
                            </div>
                            <div class="col-md-9">
                                <h3>Bảng Công Việc</h3>
                                <?php render_kanban_board($tasks, $conn); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php render_footer();
}