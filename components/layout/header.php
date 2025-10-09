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
        $basePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
        $baseCssPath = $basePath . 'css' . DIRECTORY_SEPARATOR;
        $baseJsPath = $basePath . 'js' . DIRECTORY_SEPARATOR;
        
        // CSS files
        $uiFile = $baseCssPath . 'ui.css';
        $uiV = file_exists($uiFile) ? filemtime($uiFile) : time();
        ?>
    <link rel="stylesheet" href="css/ui.css?v=<?php echo $uiV; ?>">
    <?php
    // JavaScript files
    $reminderJsFile = $baseJsPath . 'reminder_handler.js';
    $reminderJsV = file_exists($reminderJsFile) ? filemtime($reminderJsFile) : time();
    ?>
    <script src="js/reminder_handler.js?v=<?php echo $reminderJsV; ?>" defer></script>
    <?php
    $baseJsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR;
    $notifJsFile = $baseJsPath . 'handle_notifications.js';
    $notifJsV = file_exists($notifJsFile) ? filemtime($notifJsFile) : time();
    ?>
    <!-- Placeholder globals so header-included scripts can call them before script.js loads -->
    <script>
        window.showAlert = window.showAlert || function(type, message, opts) {
            // simple fallback: log and create a basic alert if container exists
            try {
                var container = document && document.getElementById && document.getElementById('global-message-container');
                if (container) {
                    var div = document.createElement('div');
                    div.className = 'alert alert-' + (type || 'info') + ' alert-dismissible fade show';
                    div.innerHTML = (message || '') + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    container.appendChild(div);
                    setTimeout(function(){ div.remove(); }, 5000);
                } else {
                    console.log('showAlert fallback:', type, message);
                }
            } catch (e) {
                console.log('showAlert fallback error', e);
            }
        };
        window.showReminderAlert = window.showReminderAlert || function(title, payload, opts) {
            window.showAlert('info', '<strong>Nhắc nhở:</strong> ' + (title || '')); 
        };
    </script>
    <script src="js/handle_notifications.js?v=<?php echo $notifJsV; ?>"></script>
    <?php
    // Ensure main script is available on all pages (including responses that may omit footer)
    $baseJsPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR;
    $scriptFile = $baseJsPath . 'script.js';
    $scriptV = file_exists($scriptFile) ? filemtime($scriptFile) : time();
    ?>
    <script src="js/script.js?v=<?php echo $scriptV; ?>" defer></script>
    </head>

    <body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white">
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
    <!-- Global message container (alerts injected here appear under header, above statistics) -->
    <div id="global-message-container" class="container mt-3"></div>
    <?php
}
    ?>