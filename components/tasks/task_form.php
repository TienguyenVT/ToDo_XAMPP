<?php
function render_task_form($edit_task = null)
{
?>
    <div class="card">
        <div class="card-body">
            <form action="index.php" method="post">
                <?php if ($edit_task): ?>
                    <input type="hidden" name="task_id" value="<?php echo $edit_task['id']; ?>">
                <?php endif; ?>
                <div class="mb-3">
                    <label for="title" class="form-label">Tiêu đề</label>
                    <input type="text" name="title" id="title" class="form-control"
                        value="<?php echo $edit_task ? htmlspecialchars($edit_task['title'] ?? '') : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea name="description" id="description" class="form-control" rows="3"><?php
                                                                                                echo $edit_task ? htmlspecialchars($edit_task['description'] ?? '') : '';
                                                                                                ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="due_date" class="form-label">Ngày hết hạn</label>
                    <input type="date" name="due_date" id="due_date" class="form-control"
                        value="<?php echo $edit_task ? htmlspecialchars($edit_task['due_date'] ?? '') : ''; ?>">
                </div>
                <div class="mb-3">
                    <label for="priority" class="form-label">Mức độ ưu tiên</label>
                    <select name="priority" id="priority" class="form-select">
                        <option value="low"
                            <?php echo ($edit_task && $edit_task['priority'] == 'low') ? 'selected' : ''; ?>>Thấp</option>
                        <option value="medium"
                            <?php echo (!$edit_task || ($edit_task && $edit_task['priority'] == 'medium')) ? 'selected' : ''; ?>>
                            Trung bình</option>
                        <option value="high"
                            <?php echo ($edit_task && $edit_task['priority'] == 'high') ? 'selected' : ''; ?>>Cao</option>
                    </select>
                </div>
                <div class="d-grid">
                    <?php if ($edit_task): ?>
                        <button type="submit" name="update_task" class="btn btn-success">Cập Nhật</button>
                        <a href="index.php" class="btn btn-secondary mt-2">Hủy</a>
                    <?php else: ?>
                        <button type="submit" name="add_task" class="btn btn-primary">Thêm Mới</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
<?php
}
?>