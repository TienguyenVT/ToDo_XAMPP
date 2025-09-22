<?php
function render_kanban_board($tasks, $conn) {
    $columns = [
        'pending' => 'Chưa hoàn thành',
        'in-progress' => 'Đang làm',
        'completed' => 'Đã hoàn thành'
    ];
?>
<div class="board" role="list">
    <?php foreach ($columns as $col_key => $col_name): ?>
    <div class="column" data-status="<?php echo $col_key; ?>" aria-label="<?php echo htmlspecialchars($col_name); ?>">
        <h4><?php echo $col_name; ?></h4>
        <div class="column-body">
            <?php
                    $filtered = array_filter($tasks, function($t) use ($col_key) {
                        return $t['status'] === $col_key;
                    });
                    if (empty($filtered)) {
                        echo '<div class="text-muted">Không có công việc</div>';
                    } else {
                        foreach ($filtered as $task) {
                            render_task_card($task, $conn);
                        }
                    }
                    ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php
}

function render_task_card($task, $conn) {
    $columns = [
        'pending' => 'Chưa hoàn thành',
        'in-progress' => 'Đang làm',
        'completed' => 'Đã hoàn thành'
    ];
    $priorityClass = 'badge-medium';
    if (isset($task['priority'])) {
        if ($task['priority'] == 'low') $priorityClass = 'badge-low';
        if ($task['priority'] == 'high') $priorityClass = 'badge-high';
    }
?>
<div class="card-item" data-task-id="<?php echo $task['id']; ?>">
    <div class="meta">
        <span
            class="badge-priority <?php echo $priorityClass; ?>"><?php echo isset($task['priority']) ? ucfirst($task['priority']) : 'Medium'; ?></span>
        <span
            class="text-muted"><?php echo !empty($task['due_date']) ? date("d/m/Y", strtotime($task['due_date'])) : 'Không có hạn'; ?></span>
    </div>
    <h5 class="mb-1 <?php echo ($task['status'] == 'completed') ? 'text-decoration-line-through' : ''; ?>">
        <?php echo htmlspecialchars($task['title']); ?></h5>
    <p class="mb-2 <?php echo ($task['status'] == 'completed') ? 'text-decoration-line-through' : ''; ?>">
        <?php echo nl2br(htmlspecialchars($task['description'])); ?></p>

<div class="d-flex flex-fill align-items-center gap-2">
    <!-- Status select for accessibility -->
    <div class="w-100 d-grid" style="grid-template-columns: 1fr auto 1fr; align-items: center;">
        <!-- Empty space for left alignment -->
        <div></div>
        
        <!-- Center content -->
        <div class="d-flex justify-content-center align-items-center gap-2">
            <form action="index.php" method="POST" class="text-center">
                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                <select name="status" class="form-select form-select-sm" style="width:270px" onchange="this.form.submit()">
                    <?php foreach ($columns as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php if ($task['status'] == $key) echo 'selected'; ?>>
                        <?php echo $label; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="update_status" value="1">
            </form>
            
            <div class="mt-2 text-center">
                <a href="index.php?edit=<?php echo $task['id']; ?>" class="btn btn-outline-warning btn-sm">Sửa</a>
                <form action="index.php" method="POST" class="d-inline">
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    <button type="submit" name="delete_task" class="btn btn-outline-danger btn-sm"
                        onclick="return confirm('Bạn có chắc chắn muốn xóa công việc này?');">Xóa</button>
                </form>
            </div>
        </div>
        
        <!-- Empty space for right alignment -->
        <div></div>
    </div>
</div>

    <?php
        require_once dirname(__FILE__) . '/reminder_component.php';
        render_reminder_component($task['id'], $conn);
        ?>
</div>
<?php
}
?>