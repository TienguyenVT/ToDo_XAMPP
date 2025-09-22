// File này có thể được sử dụng để thêm các tương tác JavaScript trong tương lai.
// Ví dụ: sử dụng AJAX để thêm/xóa công việc mà không cần tải lại trang.

document.addEventListener('DOMContentLoaded', function() {
    console.log('TodoWeb script loaded and ready.');
    // Xác nhận xóa công việc
    const deleteButtons = document.querySelectorAll('button[name="delete_task"]');
    deleteButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Bạn có chắc chắn muốn xóa công việc này?')) {
                e.preventDefault();
            }
        });
    });
    // Thông báo cập nhật trạng thái
    const statusSelects = document.querySelectorAll('select[name="status"]');
    statusSelects.forEach(function(sel) {
        sel.addEventListener('change', function() {
            alert('Trạng thái công việc đã được cập nhật!');
        });
    });
});
