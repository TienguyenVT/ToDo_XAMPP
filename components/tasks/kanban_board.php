<?php
function render_kanban_board($tasks, $conn) {
    $columns = [
        'pending' => 'Chưa hoàn thành',
        'in-progress' => 'Đang làm',
        'completed' => 'Đã hoàn thành'
    ];
?>
    <div class="row kanban-board">
        <?php foreach ($columns as $col_key => $col_name): ?>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white text-center">
                        <strong><?php echo $col_name; ?></strong>
                    </div>
                    <div class="card-body" style="min-height: 300px;">
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
?>
    <div class="kanban-task card mb-3">
        <div class="card-body">
            <h5 class="card-title <?php echo ($task['status'] == 'completed') ? 'text-decoration-line-through' : ''; ?>">
                <?php echo htmlspecialchars($task['title']); ?>
            </h5>
            <p class="card-text <?php echo ($task['status'] == 'completed') ? 'text-decoration-line-through' : ''; ?>">
                <?php echo nl2br(htmlspecialchars($task['description'])); ?>
            </p>
            <div class="mt-2">
                <small>Hạn chót: <?php echo !empty($task['due_date']) ? date("d/m/Y", strtotime($task['due_date'])) : 'Không có'; ?></small>
            </div>
            <div class="mt-2">
                <!-- Chuyển trạng thái -->
                <form action="index.php" method="POST" class="d-inline">
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    <select name="status" class="form-select form-select-sm d-inline" style="width: auto;" onchange="this.form.submit()">
                        <?php foreach ($columns as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php if ($task['status'] == $key) echo 'selected'; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="update_status" value="1">
                </form>
                <!-- Sửa công việc -->
                <a href="index.php?edit=<?php echo $task['id']; ?>" class="btn btn-warning btn-sm ms-2">Sửa</a>
                <!-- Xóa công việc -->
                <form action="index.php" method="POST" class="d-inline float-end">
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    <button type="submit" name="delete_task" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa công việc này?');">Xóa</button>
                </form>
                <?php 
require_once dirname(__FILE__) . '/reminder_component.php';
render_reminder_component($task['id'], $conn); 
?>
            </div>
        </div>
    </div>
<?php
}
?>