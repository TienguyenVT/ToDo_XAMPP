-- SQL dump for TodoWeb system (full features)
-- Create database
CREATE DATABASE IF NOT EXISTS `todoweb_db` DEFAULT CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `todoweb_db`;

-- Create users table
CREATE TABLE
    IF NOT EXISTS `users` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Create tasks table
CREATE TABLE
    IF NOT EXISTS `tasks` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT,
        `status` ENUM ('pending', 'in-progress', 'completed') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `due_date` DATE,
        `priority` ENUM ('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Create reminders table
CREATE TABLE 
    IF NOT EXISTS `reminders` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `task_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `reminder_time` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `notified_at` DATETIME DEFAULT NULL,
    FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_reminder_time` (`reminder_time`),
    INDEX `idx_user_task` (`user_id`, `task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
