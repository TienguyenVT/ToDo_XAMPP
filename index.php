<?php
session_start();

// Nếu người dùng chưa đăng nhập, chuyển hướng về trang đăng nhập
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'components/layout/header.php';
require_once 'components/layout/footer.php';
require_once 'components/tasks/task_form.php';
require_once 'components/tasks/kanban_board.php';
require_once 'components/statistics/statistics_board.php';

$user_id = $_SESSION['user_id'];
$tasks = [];
$edit_task = null;

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
        $task_id = intval($_POST['task_id']);
        $reminder_time = $_POST['reminder_time'];
        
        if (empty($task_id) || empty($reminder_time)) {
            throw new Exception("Vui lòng điền đầy đủ thông tin");
        }
        
        // Debug info
        error_log("Adding reminder - Task ID: " . $task_id . ", Time: " . $reminder_time);
        
        if (add_reminder($conn, $task_id, $reminder_time)) {
            set_flash('Thêm nhắc nhở thành công!', 'success');
        } else {
            throw new Exception("Không thể thêm nhắc nhở");
        }
    } catch (Exception $e) {
        error_log("Error adding reminder: " . $e->getMessage());
        set_flash($e->getMessage(), 'danger');
    }
    
    // Redirect sau khi xử lý
    header("location: index.php");
    exit();
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

// Render giao diện
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

<?php render_footer(); ?>