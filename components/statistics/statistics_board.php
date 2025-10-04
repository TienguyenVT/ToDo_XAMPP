<?php
function render_statistics($stats_status, $stats_priority)
{
?>
    <div class="row mt-4 mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header text-black">
                    <h3><strong>Thống kê công việc</strong></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Thống kê trạng thái - hiển thị ngang (1x3) -->
                        <div class="col-md-6">
                            <h5>Theo trạng thái:</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="list-group-item text-center">
                                        Chưa hoàn thành:
                                        <br><strong><?php echo isset($stats_status['pending']) ? $stats_status['pending'] : 0; ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="list-group-item text-center">
                                        Đang làm:
                                        <br><strong><?php echo isset($stats_status['in-progress']) ? $stats_status['in-progress'] : 0; ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="list-group-item text-center">
                                        Đã hoàn thành:
                                        <br><strong><?php echo isset($stats_status['completed']) ? $stats_status['completed'] : 0; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thống kê độ ưu tiên - hiển thị ngang (1x3) -->
                        <div class="col-md-6">
                            <h5>Theo độ ưu tiên:</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="list-group-item text-center">
                                        Thấp:
                                        <br><strong><?php echo isset($stats_priority['low']) ? $stats_priority['low'] : 0; ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="list-group-item text-center">
                                        Trung bình:
                                        <br><strong><?php echo isset($stats_priority['medium']) ? $stats_priority['medium'] : 0; ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="list-group-item text-center">
                                        Cao:
                                        <br><strong><?php echo isset($stats_priority['high']) ? $stats_priority['high'] : 0; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
?>