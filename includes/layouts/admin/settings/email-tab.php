<!-- Email Settings Tab -->
<div class="tab-pane fade" id="email-tab">
    <input type="hidden" name="email_settings" value="1">

    <!-- Mail Driver Configuration Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-cog me-2"></i>
                Cấu hình Mail Driver
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-server me-1"></i>
                            Mail Driver
                            <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="mail_driver" required>
                            <option value="smtp" <?= $additionalSettings['mail_driver'] === 'smtp' ? 'selected' : '' ?>>SMTP</option>
                            <option value="sendmail" <?= $additionalSettings['mail_driver'] === 'sendmail' ? 'selected' : '' ?>>Sendmail</option>
                            <option value="mail" <?= $additionalSettings['mail_driver'] === 'mail' ? 'selected' : '' ?>>PHP Mail</option>
                        </select>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Phương thức gửi email (khuyến nghị: SMTP)
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-globe me-1"></i>
                            SMTP Host
                        </label>
                        <input type="text" class="form-control" name="mail_host"
                               value="<?= e($additionalSettings['mail_host']) ?>"
                               placeholder="smtp.gmail.com">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Địa chỉ máy chủ SMTP
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SMTP Connection Settings Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-plug me-2"></i>
                Cài đặt kết nối SMTP
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-network-wired me-1"></i>
                            SMTP Port
                        </label>
                        <input type="number" class="form-control" name="mail_port"
                               value="<?= $additionalSettings['mail_port'] ?>"
                               placeholder="587">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Port kết nối SMTP (587 cho TLS, 465 cho SSL)
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-shield-alt me-1"></i>
                            Mã hóa
                        </label>
                        <select class="form-select" name="mail_encryption">
                            <option value="tls" <?= $additionalSettings['mail_encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= $additionalSettings['mail_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="" <?= $additionalSettings['mail_encryption'] === '' ? 'selected' : '' ?>>Không</option>
                        </select>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Phương thức mã hóa kết nối
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SMTP Authentication Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-key me-2"></i>
                Xác thực SMTP
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user me-1"></i>
                            Username
                        </label>
                        <input type="text" class="form-control" name="mail_username"
                               value="<?= e($additionalSettings['mail_username']) ?>"
                               placeholder="your-email@gmail.com">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Tên đăng nhập SMTP (thường là email)
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-lock me-1"></i>
                            Password
                        </label>
                        <input type="password" class="form-control" name="mail_password"
                               value="<?= e($additionalSettings['mail_password']) ?>"
                               placeholder="App Password">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Mật khẩu SMTP (App Password cho Gmail)
                        </div>
                    </div>
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Lưu ý:</strong> Để sử dụng Gmail SMTP, bạn cần tạo App Password trong cài đặt bảo mật Google.
            </div>
        </div>
    </div>

    <!-- Email Sender Information Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-paper-plane me-2"></i>
                Thông tin người gửi
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-1"></i>
                            From Address
                        </label>
                        <input type="email" class="form-control" name="mail_from_address"
                               value="<?= e($additionalSettings['mail_from_address']) ?>"
                               placeholder="noreply@tro365.com">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Địa chỉ email người gửi
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-signature me-1"></i>
                            From Name
                        </label>
                        <input type="text" class="form-control" name="mail_from_name"
                               value="<?= e($additionalSettings['mail_from_name']) ?>"
                               placeholder="Trọ 365">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Tên hiển thị của người gửi
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SMTP Connection Test Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="fas fa-plug me-2"></i>
                Test kết nối SMTP
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <p class="mb-3">Kiểm tra kết nối SMTP với cấu hình hiện tại trước khi gửi email test.</p>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-info w-100" id="test-smtp-connection-btn">
                        <i class="fas fa-plug me-1"></i>
                        Test kết nối SMTP
                    </button>
                </div>
            </div>
            <div id="smtp-connection-result" class="mt-3" style="display: none;"></div>
        </div>
    </div>

    <!-- Test Email Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="fas fa-paper-plane me-2"></i>
                Test gửi Email
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Email nhận test</label>
                        <input type="email" class="form-control" id="test-email-input"
                               placeholder="Nhập email để test (để trống sẽ dùng email admin)">
                        <div class="form-text">Email sẽ được gửi đến địa chỉ này để kiểm tra cấu hình</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-primary w-100" id="test-email-btn">
                            <i class="fas fa-paper-plane me-1"></i>
                            Gửi email test
                        </button>
                    </div>
                </div>
            </div>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Lưu ý:</strong> Hãy lưu cài đặt email trước khi test để áp dụng cấu hình mới.
            </div>
        </div>
    </div>
</div>
