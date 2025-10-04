#!/bin/bash

# Script để chạy như một cron job mỗi phút
# Cron expression: * * * * * /path/to/check_reminders.sh

PHP_PATH="/usr/bin/php"
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

# Chạy notification server nếu chưa chạy
if ! pgrep -f "notification_server.php" > /dev/null; then
    nohup $PHP_PATH $SCRIPT_DIR/../server/notification_server.php > $SCRIPT_DIR/../logs/notification_server.log 2>&1 &
fi