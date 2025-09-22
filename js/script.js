// File này có thể được sử dụng để thêm các tương tác JavaScript trong tương lai.
// Ví dụ: sử dụng AJAX để thêm/xóa công việc mà không cần tải lại trang.

document.addEventListener("DOMContentLoaded", function () {
  console.log("TodoWeb script loaded and ready.");
  // Event delegation for delete confirmation: handles dynamic elements too
  document.addEventListener("click", function (e) {
    var btn = e.target.closest('button[name="delete_task"]');
    if (!btn) return;
    // If an inline onclick confirm already exists, allow it to run; this is a safe double-check.
    if (!confirm("Bạn có chắc chắn muốn xóa công việc này?")) {
      e.preventDefault();
      e.stopPropagation();
    }
  });

  // Remove blocking alert on status change to avoid interrupting submit flow.
  // If you want a non-blocking notification after server confirms, use a toast mechanism.
});
