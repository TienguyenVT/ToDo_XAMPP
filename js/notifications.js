// Kết nối WebSocket và xử lý notifications
document.addEventListener('DOMContentLoaded', function() {
    let ws;
    let reconnectAttempts = 0;
    const MAX_RECONNECT_ATTEMPTS = 5;
    
    function connectWebSocket() {
        ws = new WebSocket('ws://localhost:8080');
        
        ws.onopen = function() {
            console.log('Connected to notification server');
            reconnectAttempts = 0;
            
            // Gửi thông tin xác thực
            if (window.userId) {
                ws.send(JSON.stringify({
                    type: 'auth',
                    user_id: window.userId
                }));
            }
        };
        
        ws.onmessage = function(e) {
            const data = JSON.parse(e.data);
            
            if (data.type === 'reminder') {
                showReminderAlert(data.title, data.message);
            }
        };
        
        ws.onclose = function() {
            console.log('Disconnected from notification server');
            
            // Thử kết nối lại sau 5 giây nếu chưa vượt quá số lần thử
            if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
                reconnectAttempts++;
                setTimeout(connectWebSocket, 5000);
            }
        };
        
        ws.onerror = function(err) {
            console.error('WebSocket error:', err);
        };
    }
    
    // Kết nối khi trang được load
    connectWebSocket();
    
    // Đăng ký service worker cho Push Notifications (tùy chọn)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').then(function(registration) {
            console.log('ServiceWorker registration successful');
        }).catch(function(err) {
            console.log('ServiceWorker registration failed: ', err);
        });
    }
});