// WebSocket removed - notifications handled via polling (notifications.php)
document.addEventListener('DOMContentLoaded', function() {
    console.log('WebSocket support removed: using polling for notifications');
    // If you want, trigger an immediate fetch via global fetchReminders()
    if (typeof fetchReminders === 'function') {
        try { fetchReminders(); } catch (e) { console.warn('fetchReminders error', e); }
    }
});