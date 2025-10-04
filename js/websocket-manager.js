// Initialize WebSocket connection
class WebSocketManager {
    constructor() {
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.pingInterval = 30000; // 30 seconds
        this.tabId = Math.random().toString(36).substring(7);
        this.pingTimer = null;
        this.init();
    }

    init() {
        // Only initialize if we have a user ID (user is logged in)
        if (typeof window.userId !== 'undefined') {
            this.connect();
            this.setupVisibilityHandler();
        }
    }

    connect() {
        this.ws = new WebSocket('ws://localhost:8080');

        this.ws.onopen = () => {
            console.log('Connected to notification server');
            this.reconnectAttempts = 0;
            this.authenticate();
            this.startPingInterval();
        };

        this.ws.onclose = () => {
            console.log('Disconnected from notification server');
            this.stopPingInterval();
            this.attemptReconnect();
        };

        this.ws.onerror = (error) => {
            console.error('WebSocket error:', error);
            this.stopPingInterval();
        };

        this.ws.onmessage = (event) => {
            this.handleMessage(event);
        };
    }

    authenticate() {
        if (this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type: 'auth',
                user_id: window.userId,
                tab_id: this.tabId,
                client_info: {
                    userAgent: navigator.userAgent,
                    timestamp: new Date().toISOString()
                }
            }));
        }
    }

    startPingInterval() {
        this.stopPingInterval();
        this.pingTimer = setInterval(() => this.ping(), this.pingInterval);
    }

    stopPingInterval() {
        if (this.pingTimer) {
            clearInterval(this.pingTimer);
            this.pingTimer = null;
        }
    }

    ping() {
        if (this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type: 'ping',
                tab_id: this.tabId,
                user_id: window.userId,
                status: document.hidden ? 'background' : 'active'
            }));
        }
    }

    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            setTimeout(() => this.connect(), 5000);
        }
    }

    handleMessage(event) {
        try {
            const data = JSON.parse(event.data);
            switch (data.type) {
                case 'reminder':
                    this.handleReminder(data);
                    break;
                case 'pong':
                    console.log('Received pong from server');
                    break;
            }
        } catch (error) {
            console.error('Error handling message:', error);
        }
    }

    handleReminder(data) {
        showReminderAlert(data.title, data.message);
    }

    setupVisibilityHandler() {
        document.addEventListener('visibilitychange', () => {
            if (this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify({
                    type: 'status',
                    tab_id: this.tabId,
                    user_id: window.userId,
                    status: document.hidden ? 'background' : 'active'
                }));
            }
        });

        // Handle page unload
        window.addEventListener('beforeunload', () => {
            if (this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify({
                    type: 'disconnect',
                    tab_id: this.tabId,
                    user_id: window.userId
                }));
            }
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.wsManager = new WebSocketManager();
});