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
    // Kiểm tra reminder_time không ở quá khứ
    const reminderTimeInput = form.querySelector('[name="reminder_time"]');
    if (reminderTimeInput) {
        const value = reminderTimeInput.value;
        if (value) {
            const selected = new Date(value);
            const now = new Date();
            if (selected < now) {
                if (typeof showAlert === 'function') {
                    showAlert('danger', 'Không thể đặt nhắc nhở trong quá khứ!');
                } else {
                    alert('Không thể đặt nhắc nhở trong quá khứ!');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }
        }
    }
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
        if (typeof showAlert === 'function') {
            showAlert('success', data.message || 'Đã thêm nhắc nhở thành công!');
        } else {
            alert(data.message || 'Đã thêm nhắc nhở thành công!');
        }
        // Reset form
        form.reset();
        // Cập nhật lại danh sách nhắc nhở bên dưới form
        fetchAndShowReminders();
    })
    .catch(error => {
        console.error('Error:', error);
        let errorMessage = 'Có lỗi xảy ra khi thêm nhắc nhở!';
        
        // Nếu có error message từ server
        if (error.message && typeof error.message === 'string') {
            errorMessage = error.message;
        }
        
        if (typeof showAlert === 'function') {
            showAlert('danger', errorMessage);
        } else {
            alert(errorMessage);
        }
    })
    .finally(() => {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Hiển thị danh sách nhắc nhở đã đặt dưới form
function fetchAndShowReminders() {
    // Find the container first; if not present, do nothing (this script can be included on pages without the container)
    const container = document.getElementById('reminder-list');
    if (!container) return;

    fetch('/ToDo/includes/components/list_reminders.php')
        .then(res => res.json())
        .then(data => {
            if (!data || !data.success) {
                container.innerHTML = '<div class="alert alert-danger">Không lấy được danh sách nhắc nhở!</div>';
                return;
            }

            const reminders = Array.isArray(data.reminders) ? data.reminders : [];

            let html = '<table class="table table-bordered table-sm mb-0"><thead><tr><th>Công việc</th><th>Thời gian nhắc</th><th>Trạng thái thông báo</th><th></th></tr></thead><tbody>';
            if (reminders.length === 0) {
                html += '<tr><td colspan="4" class="text-center">Chưa có nhắc nhở nào</td></tr>';
            } else {
                reminders.forEach(r => {
                    html += `<tr>
                        <td>${r.task_title}</td>
                        <td>${r.reminder_time}</td>
                        <td>${r.notified_at ? 'Đã thông báo' : 'Chưa thông báo'}</td>
                        <td><button class="btn btn-danger btn-sm btn-delete-reminder" data-reminder-id="${r.id}">Xóa</button></td>
                    </tr>`;
                });
            }
            html += '</tbody></table>';
            container.innerHTML = html;

            // Gán sự kiện xóa cho các nút
            container.querySelectorAll('.btn-delete-reminder').forEach(btn => {
                btn.addEventListener('click', function() {
                    const reminderId = this.getAttribute('data-reminder-id');
                    if (!reminderId) return;
                    if (!confirm('Bạn có chắc chắn muốn xóa nhắc nhở này?')) return;
                    btn.disabled = true;
                    fetch('index.php', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `delete_reminder=1&reminder_id=${encodeURIComponent(reminderId)}`
                    })
                    .then(res => res.text())
                    .then(text => {
                        let data;
                        try { data = JSON.parse(text); } catch { data = {}; }
                        if (data.success) {
                            if (typeof showAlert === 'function') showAlert('success', 'Đã xóa nhắc nhở!');
                            fetchAndShowReminders();
                        } else {
                            if (typeof showAlert === 'function') showAlert('danger', data.message || 'Lỗi khi xóa nhắc nhở!');
                        }
                    })
                    .catch(() => {
                        if (typeof showAlert === 'function') showAlert('danger', 'Lỗi khi xóa nhắc nhở!');
                    })
                    .finally(() => { btn.disabled = false; });
                });
            });
        })
        .catch(() => {
            // Only update if container still exists on the page
            if (container) container.innerHTML = '<div class="alert alert-danger">Lỗi khi lấy danh sách nhắc nhở!</div>';
        });
}

// Khi trang load, chỉ fetch và render vào div reminder-list đã có sẵn trong container mới
document.addEventListener('DOMContentLoaded', function() {
    fetchAndShowReminders();
});