# TodoWeb - Hệ thống Quản lý Công việc Cá nhân

Đây là dự án bài tập lớn cho môn học Cơ sở dữ liệu, xây dựng một ứng dụng web hoàn chỉnh để quản lý công việc cá nhân. Hệ thống được phát triển bằng PHP, MySQL và các công nghệ web front-end cơ bản.

## 1. Giới thiệu

TodoWeb là một ứng dụng giúp người dùng theo dõi, quản lý và sắp xếp các công việc hàng ngày một cách hiệu quả. Người dùng có thể tạo tài khoản, đăng nhập, thêm mới công việc, đặt nhắc nhở, và trực quan hóa tiến độ thông qua bảng Kanban.

## 2. Tính năng chính

- **Xác thực người dùng:** Đăng ký, đăng nhập, và đăng xuất an toàn với mật khẩu được mã hóa.
- **Quản lý Công việc (CRUD):**
  - **Tạo:** Thêm công việc mới với tiêu đề, mô tả, ngày hết hạn, và mức độ ưu tiên.
  - **Xem:** Hiển thị danh sách công việc của người dùng.
  - **Cập nhật:** Chỉnh sửa thông tin chi tiết của công việc.
  - **Xóa:** Loại bỏ công việc đã hoàn thành hoặc không cần thiết.
- **Bảng Kanban:** Trực quan hóa công việc theo ba trạng thái: `Cần làm (To Do)`, `Đang làm (In Progress)`, và `Hoàn thành (Done)`. Người dùng có thể kéo-thả để cập nhật trạng thái.
- **Lọc và Sắp xếp:**
  - Lọc công việc theo mức độ ưu tiên.
  - Sắp xếp công việc theo ngày hết hạn hoặc trạng thái.
- **Đặt nhắc nhở:** Thiết lập thời gian nhắc nhở cho các công việc quan trọng.
- **Bảng điều khiển Thống kê:** Hiển thị biểu đồ tóm tắt số lượng công việc theo trạng thái và mức độ ưu tiên.
- **Giao diện đáp ứng (Responsive):** Tương thích tốt trên các thiết bị máy tính và di động.
- **Tương tác động:** Sử dụng AJAX để cập nhật, xóa và thêm mới một số thành phần mà không cần tải lại trang.

## 3. Công nghệ sử dụng

- **Backend:** PHP 7.4+
- **Cơ sở dữ liệu:** MySQL (sử dụng `mysqli` extension)
- **Frontend:**
  - HTML5
  - CSS3
  - JavaScript (ES6)
  - [Bootstrap 5](https://getbootstrap.com/)
- **Môi trường phát triển:** XAMPP (Apache, MySQL, PHP)

## 4. Thiết kế Cơ sở dữ liệu

CSDL của hệ thống được thiết kế để lưu trữ thông tin người dùng, công việc và các nhắc nhở liên quan.

- **Bảng `users`**:
  - `id`: Khóa chính, định danh người dùng.
  - `username`, `password`, `full_name`: Lưu thông tin đăng nhập và cá nhân.
- **Bảng `tasks`**:
  - `id`: Khóa chính, định danh công việc.
  - `user_id`: Khóa ngoại, liên kết đến bảng `users` (mối quan hệ một-nhiều).
  - `title`, `description`, `status`, `priority`, `due_date`: Lưu các thuộc tính của công việc.
- **Bảng `reminders`**:
  - `id`: Khóa chính.
  - `task_id`: Khóa ngoại, liên kết đến bảng `tasks`.
  - `reminder_time`: Thời gian nhắc nhở.

File SQL để tạo CSDL và các bảng nằm tại: `sql/todoweb_db.sql`.

## 5. Hướng dẫn Cài đặt và Chạy (Local)

Để chạy dự án trên máy tính cá nhân, bạn cần cài đặt XAMPP.

1.  **Tải và Cài đặt XAMPP:**
    - Truy cập [trang chủ XAMPP](https://www.apachefriends.org/index.html) và tải phiên bản phù hợp.
    - Cài đặt XAMPP vào máy.

2.  **Clone Repository:**
    - Clone mã nguồn của dự án này vào thư mục `htdocs` của XAMPP.
      ```bash
      cd C:\xampp\htdocs
      git clone <URL_CUA_REPO_NAY> ToDo
      ```
    - Hoặc đơn giản là sao chép thư mục dự án vào `C:\xampp\htdocs`.

3.  **Khởi động XAMPP:**
    - Mở `XAMPP Control Panel`.
    - Nhấn `Start` cho module **Apache** và **MySQL**.

4.  **Import Cơ sở dữ liệu:**
    - Mở trình duyệt và truy cập `http://localhost/phpmyadmin`.
    - Tạo một cơ sở dữ liệu mới với tên là `todoweb_db`.
    - Chọn CSDL vừa tạo, vào tab `Import`.
    - Nhấn `Choose File` và chọn file `sql/todoweb_db.sql` trong thư mục dự án.
    - Nhấn `Go` để bắt đầu import.

5.  **Truy cập ứng dụng:**
    - Mở trình duyệt và truy cập vào địa chỉ: `http://localhost/ToDo`
    - Bạn có thể bắt đầu bằng việc đăng ký một tài khoản mới.

## 6. Hướng dẫn Deploy

Dự án này đã được cấu hình để có thể deploy lên các nền tảng hosting hỗ trợ PHP và MySQL. Hướng dẫn chi tiết để deploy lên nền tảng Railway có thể được tìm thấy trong file `README-deploy-railway.md`.


