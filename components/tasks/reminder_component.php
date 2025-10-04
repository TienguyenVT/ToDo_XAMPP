<?php
function render_reminder_component($task_id, $conn)
{
?>
    <button type="button" class="mt-3 btn btn-info btn-sm w-100" data-bs-toggle="collapse"
        data-bs-target="#reminder-<?php echo $task_id; ?>">Nhắc nhở
    </button>
    <div class="collapse mt-2" id="reminder-<?php echo $task_id; ?>">
        <form action="index.php" method="POST" class="d-flex align-items-center reminder-form">
            <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
            <input type="datetime-local" name="reminder_time" class="form-control form-control-sm me-2" required 
                   min="<?php echo date('Y-m-d\TH:i'); ?>">
            <button type="submit" name="add_reminder" class="btn btn-success btn-sm w-100">
                <span class="button-text">Thêm nhắc nhở</span>
            </button>
        </form>
        <?php $reminders = get_reminders($conn, $task_id); ?>
        <?php if (!empty($reminders)): ?>
            <ul class="list-group list-group-flush mt-2">
                <?php foreach ($reminders as $rem): ?>
                    <li class="list-group-item py-1 d-flex justify-content-between align-items-center">
                        <span><?php echo date('d/m/Y H:i', strtotime($rem['reminder_time'])); ?></span>
                        <form action="index.php" method="POST" class="d-inline">
                            <input type="hidden" name="reminder_id" value="<?php echo $rem['id']; ?>">
                            <button type="submit" name="delete_reminder" class="btn btn-sm w-100">X</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php
}
?>