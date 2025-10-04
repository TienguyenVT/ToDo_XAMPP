// Xử lý form reminder
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý tất cả các form reminder
    document.querySelectorAll('form').forEach(form => {
        if (form.querySelector('button[name="add_reminder"]')) {
            form.addEventListener('submit', handleReminderSubmit);
        }
    });
});

// Hàm xử lý submit form reminder
function handleReminderSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[name="add_reminder"]');
    const originalText = submitBtn.innerHTML;
    
    // Disable button và hiển thị trạng thái đang xử lý
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';

    // Lấy dữ liệu form
    const formData = new FormData(form);
    // Ensure server sees this as an add_reminder action when submitting via AJAX
    if (!formData.has('add_reminder')) {
        formData.append('add_reminder', '1');
    }

    // Log data trước khi gửi
    console.log('Sending reminder data:', Object.fromEntries(formData));
    
    // Gửi request
    fetch('index.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.text().then(text => {
        // Log raw response for debugging (could be empty / HTML / JSON)
        console.log('Raw server response:', text);
        if (!response.ok) {
            // Try to parse JSON error message if present
            try {
                const parsed = JSON.parse(text);
                throw new Error(parsed.message || 'Network response was not ok');
            } catch (err) {
                throw new Error(text || 'Network response was not ok');
            }
        }
        // Parse json body (may throw)
        try {
            const data = JSON.parse(text);
            if (!data.success) {
                throw new Error(data.message || 'Có lỗi xảy ra khi thêm nhắc nhở');
            }
            return data;
        } catch (err) {
            throw new Error(text || 'Invalid JSON in response');
        }
    }))
    .then(data => {
        // Hiển thị thông báo thành công
        showAlert('success', data.message || 'Đã thêm nhắc nhở thành công!');
        
        // Reset form
        form.reset();
        
        // Reload trang sau 1 giây
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    })
    .catch(error => {
        console.error('Error:', error);
        let errorMessage = 'Có lỗi xảy ra khi thêm nhắc nhở!';
        
        // Nếu có error message từ server
        if (error.message && typeof error.message === 'string') {
            errorMessage = error.message;
        }
        
        showAlert('danger', errorMessage);
    })
    .finally(() => {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Hàm hiển thị thông báo
function showAlert(type, message) {
    // Use the shared global-message-container and insert HTML so script.js's
    // insertAdjacentHTML override will schedule auto-dismiss and adjust layout.
    const container = document.getElementById('global-message-container');
    const safeType = ['success','danger','warning','info'].includes(type) ? type : 'info';
    const html = `<div class="alert alert-${safeType} alert-dismissible fade" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
    if (container && typeof container.insertAdjacentHTML === 'function') {
        container.insertAdjacentHTML('beforeend', html);
        // script.js overrides insertAdjacentHTML to add .show and schedule auto-dismiss
    } else if (container) {
        // Fallback: create and append
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${safeType} alert-dismissible fade show`;
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        container.appendChild(alertDiv);
        // simple auto remove fallback
        setTimeout(() => { try { alertDiv.remove(); } catch(e){} }, 5000);
    } else {
        // Last resort: native alert
        alert(message);
    }
}