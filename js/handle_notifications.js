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
                    createNotification(notification);
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

// Hàm tạo và hiển thị notification
function createNotification(notification) {
    const container = document.getElementById('notification-container') || createNotificationContainer();
    
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-info alert-dismissible fade show';
    alertDiv.role = 'alert';
    
    alertDiv.innerHTML = `
        <strong>Nhắc nhở!</strong> ${notification.message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    container.appendChild(alertDiv);
    
    // Tự động ẩn sau 5 giây
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
        alert.close();
    }, 5000);
}

// Tạo container cho notifications nếu chưa tồn tại
function createNotificationContainer() {
    const container = document.createElement('div');
    container.id = 'notification-container';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
    `;
    document.body.appendChild(container);
    return container;
}

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