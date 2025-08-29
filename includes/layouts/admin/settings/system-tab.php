<!-- System Settings Tab -->
<div class="tab-pane fade" id="system-tab">
    <input type="hidden" name="system_settings" value="1">

    <!-- Business Settings Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-business-time me-2"></i>
                Cài đặt kinh doanh
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-percentage me-1"></i>
                            Tỷ lệ hoa hồng (%)
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" name="ty_le_hoa_hong"
                               value="<?= $settings['ty_le_hoa_hong'] ?>" min="0" max="100" step="0.1" required>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Tỷ lệ hoa hồng cho admin khi giao dịch thành công
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-clock me-1"></i>
                            Thời gian hiệu lực bài đăng (ngày)
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" name="thoi_gian_hieu_luc_bai_dang"
                               value="<?= $settings['thoi_gian_hieu_luc_bai_dang'] ?>" min="1" max="365" required>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Số ngày bài đăng có hiệu lực trước khi hết hạn
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Display Settings Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-display me-2"></i>
                Cài đặt hiển thị
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-list me-1"></i>
                            Số bài đăng mỗi trang
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" name="so_bai_dang_moi_trang"
                               value="<?= $settings['so_bai_dang_moi_trang'] ?>" min="5" max="100" required>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Số lượng bài đăng hiển thị trên mỗi trang
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- File Upload Settings Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-upload me-2"></i>
                Cài đặt upload file
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-weight-hanging me-1"></i>
                            Kích thước upload tối đa (MB)
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" name="max_upload_size"
                               value="<?= $additionalSettings['max_upload_size'] ?>" min="1" max="50" required>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Kích thước tối đa cho mỗi file upload
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-file-alt me-1"></i>
                            Loại file được phép upload
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="allowed_file_types"
                               value="<?= e($additionalSettings['allowed_file_types']) ?>" required>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Các loại file cách nhau bởi dấu phẩy (vd: jpg,png,pdf)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Registration & System Settings Section -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        Đăng ký người dùng
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="enable_registration" id="enable_registration"
                               <?= $additionalSettings['enable_registration'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_registration">
                            <i class="fas fa-user me-1"></i>
                            Cho phép đăng ký người dùng
                        </label>
                        <div class="form-text">Cho phép người dùng mới đăng ký tài khoản</div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="enable_seller_registration" id="enable_seller_registration"
                               <?= $additionalSettings['enable_seller_registration'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_seller_registration">
                            <i class="fas fa-store me-1"></i>
                            Cho phép đăng ký seller
                        </label>
                        <div class="form-text">Cho phép người dùng đăng ký làm seller</div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="require_email_verification" id="require_email_verification"
                               <?= $additionalSettings['require_email_verification'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="require_email_verification">
                            <i class="fas fa-envelope-check me-1"></i>
                            Yêu cầu xác thực email
                        </label>
                        <div class="form-text">Bắt buộc xác thực email khi đăng ký</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Bảo trì hệ thống
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="enable_maintenance_mode" id="enable_maintenance_mode"
                               <?= $additionalSettings['enable_maintenance_mode'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_maintenance_mode">
                            <i class="fas fa-wrench me-1"></i>
                            Bật chế độ bảo trì
                        </label>
                        <div class="form-text mt-2">
                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                            Khi bật, chỉ admin mới có thể truy cập website
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="app_debug" id="app_debug"
                               <?= $additionalSettings['app_debug'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="app_debug">
                            <i class="fas fa-bug me-1"></i>
                            Bật chế độ debug
                        </label>
                        <div class="form-text mt-2">
                            <i class="fas fa-info-circle text-info me-1"></i>
                            Hiển thị thông báo debug, status badge và thông tin lỗi chi tiết
                        </div>

                        <?php if (isDebugModeEnabled()): ?>
                        <div class="debug-panel-container mt-3">
                            <div class="alert alert-success">
                                <h6><i class="fas fa-bug"></i> Debug Mode Active</h6>
                                <div class="debug-info">
                                    <small>
                                        <strong>PHP Version:</strong> <?= PHP_VERSION ?><br>
                                        <strong>Memory Usage:</strong> <?= formatBytes(memory_get_usage(true)) ?><br>
                                        <strong>Session ID:</strong> <?= session_id() ?><br>
                                        <strong>Log Path:</strong> <?= LOG_PATH ?><br>
                                        <strong>Debug Status:</strong> <span class="text-success">ENABLED</span><br>
                                        <strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Management Section -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-home me-2"></i>
                        Cấu hình số phòng
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="max_rooms_per_post" class="form-label">
                            <i class="fas fa-door-open me-1"></i>
                            Số phòng tối đa mỗi bài đăng
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                               class="form-control"
                               id="max_rooms_per_post"
                               name="max_rooms_per_post"
                               value="<?= e($config->getValue('max_rooms_per_post', '50')) ?>"
                               min="1"
                               max="1000"
                               required>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Giới hạn số phòng tối đa mà seller có thể đăng trong một bài đăng.
                            Mặc định: 50 phòng.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-success btn-sm" onclick="saveRoomLimitSettings()">
                            <i class="fas fa-save me-1"></i>
                            Lưu cấu hình
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Thống kê số phòng
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    // Get room statistics
                    $totalPosts = $db->count('BaiDang', '1=1');
                    $avgRooms = $db->selectOne("SELECT AVG(SoPhong) as avg_rooms FROM BaiDang WHERE SoPhong > 0")['avg_rooms'] ?? 0;
                    $maxRooms = $db->selectOne("SELECT MAX(SoPhong) as max_rooms FROM BaiDang")['max_rooms'] ?? 0;
                    $currentLimit = $config->getValue('max_rooms_per_post', '50');
                    ?>
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary mb-1"><?= number_format($avgRooms, 1) ?></h4>
                                <small class="text-muted">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    Trung bình phòng/bài
                                </small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info mb-1"><?= $maxRooms ?></h4>
                            <small class="text-muted">
                                <i class="fas fa-arrow-up me-1"></i>
                                Tối đa hiện tại
                            </small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h5 class="text-success mb-1"><?= $currentLimit ?></h5>
                        <small class="text-muted">
                            <i class="fas fa-cog me-1"></i>
                            Giới hạn được thiết lập
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
