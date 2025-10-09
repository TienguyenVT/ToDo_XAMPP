// Function to show alert messages
function showAlert(type, message) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    
    // Add message
    alertDiv.innerHTML = message;
    
    // Add close button
    const closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.className = 'btn-close';
    closeButton.setAttribute('data-bs-dismiss', 'alert');
    closeButton.setAttribute('aria-label', 'Close');
    alertDiv.appendChild(closeButton);
    
    // Find the message container
    const container = document.getElementById('global-message-container');
    if (container) {
        // Insert alert at the top of the container
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 5000);
    }
}

// Function to show reminder alerts
function showReminderAlert(title, reminderTime) {
    let message = `<strong>Nhắc nhở:</strong> ${title}`;
    if (reminderTime) {
        message += ` <small class="text-muted">(${reminderTime})</small>`;
    }
    showAlert('info', message);
}