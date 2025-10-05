<?php

// Include các component
require_once __DIR__ . '/components/tasks.php';
require_once __DIR__ . '/components/reminders.php';
require_once __DIR__ . '/components/statistics.php';
require_once __DIR__ . '/components/messages.php';

// Shared helper used to format notification messages consistently across the app
if (!function_exists('format_notification_message')) {
	function format_notification_message($task_title, $reminder_time) {
		$formatted_time = date('H:i d/m/Y', strtotime($reminder_time));
		return sprintf('Nhắc nhở: Công việc "%s" đến hạn lúc %s', $task_title, $formatted_time);
	}
}
