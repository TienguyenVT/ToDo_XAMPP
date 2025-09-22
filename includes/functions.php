<?php

/**
 * File này đóng vai trò như một điểm truy cập trung tâm cho tất cả các chức năng.
 * Mọi chức năng được tổ chức thành các component riêng biệt trong thư mục components/
 * giúp code dễ đọc, dễ bảo trì và dễ mở rộng.
 * 
 * Cấu trúc components:
 * - tasks.php: Quản lý công việc (thêm, sửa, xóa, cập nhật trạng thái)
 * - (categories removed) Previously categories.php managed categories; feature removed.
 * - reminders.php: Quản lý nhắc nhở cho công việc
 * - time_tracking.php: Theo dõi thời gian làm việc
 * - statistics.php: Thống kê và báo cáo
 */

// Include các component
require_once __DIR__ . '/components/tasks.php';
require_once __DIR__ . '/components/reminders.php';
require_once __DIR__ . '/components/statistics.php';
