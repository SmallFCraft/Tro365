<!-- Website Settings Tab -->
<div class="tab-pane fade show active" id="website-tab">
    <input type="hidden" name="website_settings" value="1">

    <!-- Basic Website Information Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-globe me-2"></i>
                Thông tin cơ bản website
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-tag me-1"></i>
                            Tên website
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="ten_website"
                               value="<?= e($settings['ten_website']) ?>" required
                               minlength="3" maxlength="100">
                        <div class="invalid-feedback">
                            Vui lòng nhập tên website (3-100 ký tự).
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Tên hiển thị chính của website
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-1"></i>
                            Email quản trị
                            <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" name="email_admin"
                               value="<?= e($settings['email_admin']) ?>" required>
                        <div class="invalid-feedback">
                            Vui lòng nhập email quản trị hợp lệ.
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Email chính của quản trị viên
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-align-left me-1"></i>
                    Mô tả website
                    <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" name="mo_ta_website" rows="3" required><?= e($settings['mo_ta_website']) ?></textarea>
                <div class="form-text">
                    <i class="fas fa-info-circle me-1"></i>
                    Mô tả ngắn gọn về website, hiển thị trong footer và meta description
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-address-book me-2"></i>
                Thông tin liên hệ
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            Địa chỉ công ty
                        </label>
                        <input type="text" class="form-control" name="dia_chi_cong_ty"
                               value="<?= e($settings['dia_chi_cong_ty']) ?>">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Địa chỉ trụ sở chính của công ty
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-phone me-1"></i>
                            Hotline
                        </label>
                        <input type="text" class="form-control" name="sdt_hotline"
                               value="<?= e($settings['sdt_hotline']) ?>"
                               placeholder="0387368890">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Số điện thoại hỗ trợ khách hàng
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope me-1"></i>
                            Email liên hệ
                        </label>
                        <input type="email" class="form-control" name="email_lien_he"
                               value="<?= e($settings['email_lien_he']) ?>"
                               placeholder="contact@tro365.com">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Email hỗ trợ khách hàng
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-share-alt me-2"></i>
                Mạng xã hội
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fab fa-facebook me-1"></i>
                            Facebook URL
                        </label>
                        <input type="url" class="form-control" name="facebook_url"
                               value="<?= e($settings['facebook_url']) ?>"
                               placeholder="https://facebook.com/tro365">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Đường dẫn đến trang Facebook chính thức
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-comments me-1"></i>
                            Zalo URL
                        </label>
                        <input type="url" class="form-control" name="zalo_url"
                               value="<?= e($settings['zalo_url']) ?>"
                               placeholder="https://zalo.me/tro365">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Đường dẫn đến Zalo OA hoặc nhóm hỗ trợ
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
