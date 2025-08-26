<!-- Advanced Settings Tab -->
<div class="tab-pane fade" id="advanced-tab">
    <div class="row">
        <!-- Version Management -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-code-branch me-2"></i>
                        Quản lý phiên bản
                    </h6>
                </div>
                <div class="card-body">
                    <div id="versionForm">
                        <div class="mb-3">
                            <label for="app_version" class="form-label">
                                <i class="fas fa-tag me-1"></i>
                                Phiên bản ứng dụng
                            </label>
                            <div class="input-group mb-2">
                                <span class="input-group-text">v</span>
                                <input type="text"
                                       class="form-control"
                                       id="app_version"
                                       name="app_version"
                                       value="<?= getAppVersion() ?>"
                                       pattern="^\d+\.\d+\.\d+$"
                                       placeholder="1.0.0"
                                       title="Định dạng: x.y.z (ví dụ: 1.2.3)"
                                       required>
                            </div>
                            <div class="mb-2">
                                <input type="text"
                                       class="form-control"
                                       id="version_description"
                                       name="version_description"
                                       placeholder="Mô tả phiên bản (tùy chọn)"
                                       maxlength="200">
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Để trống để tự động tạo mô tả dựa trên loại cập nhật
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="button" class="btn btn-primary" onclick="updateVersion()">
                                    <i class="fas fa-save me-1"></i>
                                    Cập nhật phiên bản
                                </button>
                            </div>
                            <div class="form-text mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Định dạng: Major.Minor.Patch (ví dụ: 1.2.3)
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <h6 class="mb-2">
                            <i class="fas fa-history me-1"></i>
                            Lịch sử phiên bản
                        </h6>
                        <div class="version-history" style="max-height: 200px; overflow-y: auto;">
                            <?php
                            $versionHistory = getVersionHistory();
                            if (!empty($versionHistory)):
                                foreach ($versionHistory as $index => $entry):
                                    $isCurrentVersion = ($index === 0);
                                    $badgeClass = $isCurrentVersion ? 'bg-success' : 'bg-secondary';
                                    $versionDate = date('d/m/Y H:i', strtotime($entry['date']));
                            ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                    <div class="flex-grow-1">
                                        <span class="badge <?= $badgeClass ?> me-2">v<?= e($entry['version']) ?></span>
                                        <small class="text-muted"><?= e($entry['description']) ?></small>
                                        <?php if (!empty($entry['is_custom_description'])): ?>
                                            <i class="fas fa-pen text-primary ms-1" title="Mô tả tùy chỉnh"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-2"><?= $versionDate ?></small>
                                        <button class="btn btn-sm btn-outline-secondary edit-version-btn"
                                                data-version="<?= e($entry['version']) ?>"
                                                data-description="<?= e($entry['description']) ?>"
                                                title="Chỉnh sửa mô tả"
                                                type="button">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php
                                endforeach;
                            else:
                            ?>
                                <small class="text-muted d-block">Chưa có lịch sử phiên bản</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-database me-2"></i>
                        Thông tin hệ thống
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>App Version:</strong></td>
                            <td>
                                <span class="badge bg-primary">v<?= getAppVersion() ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>PHP Version:</strong></td>
                            <td><?= PHP_VERSION ?></td>
                        </tr>
                        <tr>
                            <td><strong>MySQL Version:</strong></td>
                            <td>
                                <?php
                                try {
                                    $version = $db->selectOne("SELECT VERSION() as version");
                                    echo $version['version'] ?? 'N/A';
                                } catch (Exception $e) {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Server:</strong></td>
                            <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></td>
                        </tr>
                        <tr>
                            <td><strong>Upload Max Size:</strong></td>
                            <td><?= ini_get('upload_max_filesize') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Memory Limit:</strong></td>
                            <td><?= ini_get('memory_limit') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Admin Tools -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-tools me-2"></i>
                        Công cụ quản trị
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-warning" onclick="Tro365Settings.clearCache()" title="Xóa cache hệ thống và log files">
                            <i class="fas fa-trash me-1"></i>
                            Xóa Cache & Logs
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="exportSystemInfo()">
                            <i class="fas fa-download me-1"></i>
                            Xuất thông tin hệ thống
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="resetToDefault()">
                            <i class="fas fa-undo me-1"></i>
                            Khôi phục mặc định
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TinyMCE Settings -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-edit me-2"></i>
                Cấu hình TinyMCE Editor
            </h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="tinymce_api_key" class="form-label">
                    <i class="fas fa-key me-1"></i>
                    TinyMCE API Key
                    <span class="text-danger">*</span>
                </label>
                <input type="text"
                       class="form-control"
                       id="tinymce_api_key"
                       name="tinymce_api_key"
                       value="<?= e($config->getValue('tinymce_api_key', '')) ?>"
                       placeholder="Nhập TinyMCE API Key..."
                       required>
                <div class="form-text">
                    <i class="fas fa-info-circle me-1"></i>
                    API Key để sử dụng TinyMCE Rich Text Editor.
                    <a href="https://www.tiny.cloud/auth/signup/" target="_blank">Đăng ký miễn phí tại đây</a>
                </div>
            </div>

            <div class="text-end">
                <button type="button" class="btn btn-success btn-sm" onclick="saveTinyMCESettings()">
                    <i class="fas fa-save me-1"></i>
                    Lưu cấu hình
                </button>
            </div>
        </div>
    </div>
</div>
