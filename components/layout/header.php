<?php
function render_header($user_full_name)
{
?>
    <!DOCTYPE html>
    <html lang="vi">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bảng Điều Khiển - TodoWeb</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <?php
        // Cache-busting using file modification time so browser reloads when files change
        $baseCssPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR;
        $uiFile = $baseCssPath . 'ui.css';
        $uiV = file_exists($uiFile) ? filemtime($uiFile) : time();
        ?>
        <link rel="stylesheet" href="css/ui.css?v=<?php echo $uiV; ?>">
    </head>

    <body>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container d-flex align-items-center justify-content-between">
                <!-- Brand -->
                <div class="d-flex align-items-center">
                    <a class="navbar-brand me-3" href="#">TodoWeb</a>
                </div>

                <!-- Search + Priority filter (center) -->
                <div class="flex-fill d-flex justify-content-center">
                    <form action="index.php" method="get" class="d-flex w-100 justify-content-center"
                        style="max-width:760px;">
                        <input type="text" name="search" class="form-control me-2"
                            placeholder="Tìm kiếm công việc theo tiêu đề hoặc mô tả"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <select name="priority_filter" class="form-select me-2" style="width:150px">
                            <option value="">Mọi mức độ</option>
                            <option value="low"
                                <?php echo (isset($_GET['priority_filter']) && $_GET['priority_filter'] == 'low') ? 'selected' : ''; ?>>
                                Thấp</option>
                            <option value="medium"
                                <?php echo (isset($_GET['priority_filter']) && $_GET['priority_filter'] == 'medium') ? 'selected' : ''; ?>>
                                Trung bình</option>
                            <option value="high"
                                <?php echo (isset($_GET['priority_filter']) && $_GET['priority_filter'] == 'high') ? 'selected' : ''; ?>>
                                Cao</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Tìm</button>
                    </form>
                </div>

                <!-- User info + logout (right) -->
                <div class="d-flex align-items-center">
                    <span class="navbar-text text-white me-3 d-none d-md-inline">
                        Xin chào, <strong><?php echo htmlspecialchars($user_full_name); ?></strong>
                    </span>
                    <a href="logout.php" class="btn btn-danger">Đăng Xuất</a>
                </div>
            </div>
        </nav>
    <?php
}
    ?>