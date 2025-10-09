<?php
// Minimal CLI test script for tasks create/update/delete
// Run: php tests/test_tasks_cli.php

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/components/tasks.php';

function ok($msg) { echo "[OK] $msg\n"; }
function err($msg) { echo "[ERR] $msg\n"; }

// Use a fake user id for testing. Make sure this user exists in the users table.
// Try to find a user; if none, create a temporary user and remove it at the end.
$user_id = null;
$temp_user_created = false;

$res = $conn->query("SELECT id FROM users LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $user_id = (int)$row['id'];
    ok("Using existing user_id=$user_id for tests");
} else {
    // create a temp user
    $username = 'test_user_' . time();
    $password = password_hash('password', PASSWORD_DEFAULT);
    $full_name = 'Test User';
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $username, $password, $full_name);
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        $temp_user_created = true;
        ok("Created temporary user_id=$user_id");
    } else {
        err("Failed to create temporary user: " . $stmt->error);
        exit(2);
    }
}

$tests_passed = true;

// 1) Create task with missing description and due_date (empty strings)
$title = 'CLI Test Task ' . time();
$description = ''; // intentionally empty
$due_date = ''; // intentionally empty
$priority = ''; // empty -> should default to 'medium'

$created = create_task($conn, $user_id, $title, $description, $due_date, $priority);
if ($created) {
    ok('create_task returned true');
    // fetch last inserted task for this user and title
    $stmt = $conn->prepare("SELECT id, title, description, due_date, priority FROM tasks WHERE user_id = ? AND title = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('is', $user_id, $title);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    if ($task) {
        ok('Inserted task found: id=' . $task['id']);
        $task_id = (int)$task['id'];
        // Check description is NULL or empty
        if ($task['description'] === null || $task['description'] === '') {
            ok('description is empty/NULL as expected');
        } else {
            err('description unexpected: ' . $task['description']);
            $tests_passed = false;
        }
        // due_date should be NULL
        if ($task['due_date'] === null || $task['due_date'] === '') {
            ok('due_date is NULL/empty as expected');
        } else {
            err('due_date unexpected: ' . $task['due_date']);
            $tests_passed = false;
        }
        // priority should default to 'medium'
        if ($task['priority'] === 'medium') {
            ok('priority defaulted to medium');
        } else {
            err('priority unexpected: ' . $task['priority']);
            $tests_passed = false;
        }
    } else {
        err('Inserted task not found');
        $tests_passed = false;
    }
} else {
    err('create_task returned false');
    $tests_passed = false;
}

// 2) Update task with empty title (should keep old title), empty description -> keep old, set priority to 'high'
if (isset($task_id)) {
    $new_title = ''; // empty => should keep existing
    $new_description = ''; // empty => keep existing
    $new_due_date = ''; // empty => keep existing
    $new_priority = 'high';

    $updated = update_task($conn, $task_id, $user_id, $new_title, $new_description, $new_due_date, $new_priority);
    if ($updated) {
        ok('update_task returned true');
        $updated_task = get_task_detail($conn, $task_id, $user_id);
        if ($updated_task) {
            if ($updated_task['title'] === $title) {
                ok('title unchanged as expected');
            } else {
                err('title changed unexpectedly: ' . $updated_task['title']);
                $tests_passed = false;
            }
            if ($updated_task['priority'] === 'high') {
                ok('priority updated to high');
            } else {
                err('priority not updated: ' . $updated_task['priority']);
                $tests_passed = false;
            }
        } else {
            err('Failed to fetch updated task');
            $tests_passed = false;
        }
    } else {
        err('update_task returned false');
        $tests_passed = false;
    }
}

// Cleanup: delete created task(s)
if (isset($task_id)) {
    $deleted = delete_task($conn, $task_id, $user_id);
    if ($deleted) {
        ok('Deleted test task id=' . $task_id);
    } else {
        err('Failed to delete test task id=' . $task_id);
        $tests_passed = false;
    }
}

// If we created a temporary user, delete it
if ($temp_user_created && isset($user_id)) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        ok('Deleted temporary user id=' . $user_id);
    } else {
        err('Failed to delete temporary user id=' . $user_id);
        $tests_passed = false;
    }
}

if ($tests_passed) {
    ok('ALL TESTS PASSED');
    exit(0);
} else {
    err('SOME TESTS FAILED');
    exit(1);
}
