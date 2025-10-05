<?php
// Session-based flash message helpers
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

function set_flash($message, $type = 'success')
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['flash'] = ['msg' => $message, 'type' => $type];
}

function get_flash()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function display_flash()
{
    $f = get_flash();
    if ($f) {
        $allowed = ['success', 'danger', 'warning', 'info'];
        $type = in_array($f['type'], $allowed) ? $f['type'] : 'info';
        $msg = htmlspecialchars($f['msg'], ENT_QUOTES);
        // Enqueue server flash into JS queue; script.js will insert into #global-message-container when ready
        $jsMsg = addslashes($msg);
        echo "<script>window.__SERVER_FLASH__ = window.__SERVER_FLASH__ || []; window.__SERVER_FLASH__.push({msg: '" . $jsMsg . "', type: '" . $type . "'});</script>";
    }
}

?>
