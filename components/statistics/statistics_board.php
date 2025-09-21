<?php
function render_statistics($stats_status, $stats_priority) {
?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <strong>Báo cáo thống kê công việc</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Số lượng theo trạng thái</h6>
                            <ul class="list-group">
                                <li class="list-group-item">Chưa hoàn thành: <strong><?php echo isset($stats_status['pending']) ? $stats_status['pending'] : 0; ?></strong></li>
                                <li class="list-group-item">Đang làm: <strong><?php echo isset($stats_status['in-progress']) ? $stats_status['in-progress'] : 0; ?></strong></li>
                                <li class="list-group-item">Đã hoàn thành: <strong><?php echo isset($stats_status['completed']) ? $stats_status['completed'] : 0; ?></strong></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>Số lượng theo mức độ ưu tiên</h6>
                            <ul class="list-group">
                                <li class="list-group-item">Thấp: <strong><?php echo isset($stats_priority['low']) ? $stats_priority['low'] : 0; ?></strong></li>
                                <li class="list-group-item">Trung bình: <strong><?php echo isset($stats_priority['medium']) ? $stats_priority['medium'] : 0; ?></strong></li>
                                <li class="list-group-item">Cao: <strong><?php echo isset($stats_priority['high']) ? $stats_priority['high'] : 0; ?></strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>