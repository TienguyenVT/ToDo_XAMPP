// Hàm chính để kiểm tra notifications
function checkNotifications() {
    fetch('/ToDo/includes/components/check_notifications.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('Received notifications:', data);
            if (data.success && data.notifications && data.notifications.length > 0) {
                data.notifications.forEach(notification => {
                    // Unified rendering: always use the global UI handlers which target
                    // #global-message-container and are styled by ui.css. This removes
                    // the old floating notification code path.
                    try {
                        if (typeof showReminderAlert === 'function' && notification.task_title) {
                            showReminderAlert(notification.task_title, notification.scheduled_at || notification.reminder_time || '');
                        } else if (typeof showAlert === 'function') {
                            showAlert('info', notification.message);
                        } else {
                            // If neither function exists, log an error so we can fix integration.
                            console.error('No global notification renderer available for', notification);
                        }
                    } catch (e) {
                        console.error('Error rendering notification via global UI:', e, notification);
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error checking notifications:', error);
            // Ghi log chi tiết hơn để debug
            if (error.message.includes('Invalid JSON')) {
                console.error('Server might be returning HTML error instead of JSON');
            }
        });
}

// Legacy floating notification UI removed. Notifications render via showAlert/showReminderAlert

// Khởi động kiểm tra notification
document.addEventListener('DOMContentLoaded', function() {
    // Kiểm tra ngay khi trang load
    checkNotifications();
    
    // Thiết lập interval để kiểm tra định kỳ (mỗi 10 giây)
    setInterval(checkNotifications, 10000);
    
    // Kiểm tra khi tab được kích hoạt
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            checkNotifications();
        }
    });
});