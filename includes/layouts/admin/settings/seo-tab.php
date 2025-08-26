<!-- SEO Settings Tab -->
<div class="tab-pane fade" id="seo-tab">
    <input type="hidden" name="seo_settings" value="1">

    <!-- Meta Tags Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-tags me-2"></i>
                Meta Tags
            </h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-key me-1"></i>
                    Meta Keywords
                </label>
                <input type="text" class="form-control" name="meta_keywords"
                       value="<?= e($additionalSettings['meta_keywords']) ?>"
                       placeholder="thuê trọ, phòng trọ, nhà trọ, tìm trọ">
                <div class="form-text">
                    <i class="fas fa-info-circle me-1"></i>
                    Các từ khóa cách nhau bởi dấu phẩy (ít quan trọng với Google hiện tại)
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-align-left me-1"></i>
                    Meta Description
                </label>
                <textarea class="form-control" name="meta_description" rows="3"
                          placeholder="Mô tả ngắn gọn về website của bạn"><?= e($additionalSettings['meta_description']) ?></textarea>
                <div class="form-text">
                    <i class="fas fa-info-circle me-1"></i>
                    Mô tả này sẽ hiển thị trong kết quả tìm kiếm Google (nên dài 150-160 ký tự)
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics & Tracking Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-chart-line me-2"></i>
                Analytics & Tracking
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fab fa-google me-1"></i>
                            Google Analytics ID
                        </label>
                        <input type="text" class="form-control" name="google_analytics_id"
                               value="<?= e($additionalSettings['google_analytics_id']) ?>"
                               placeholder="G-XXXXXXXXXX">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Mã theo dõi Google Analytics 4 để phân tích lưu lượng truy cập
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-search me-1"></i>
                            Google Search Console
                        </label>
                        <input type="text" class="form-control" name="google_search_console"
                               value="<?= e($additionalSettings['google_search_console']) ?>"
                               placeholder="google-site-verification=...">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Mã xác thực Google Search Console để theo dõi SEO
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fab fa-facebook me-1"></i>
                            Facebook Pixel ID
                        </label>
                        <input type="text" class="form-control" name="facebook_pixel_id"
                               value="<?= e($additionalSettings['facebook_pixel_id']) ?>"
                               placeholder="123456789012345">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            ID của Facebook Pixel để theo dõi chuyển đổi và tối ưu quảng cáo
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEO Tools Section -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-sitemap me-2"></i>
                        Sitemap
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_sitemap" id="enable_sitemap"
                               <?= $additionalSettings['enable_sitemap'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_sitemap">
                            <i class="fas fa-map me-1"></i>
                            Bật sitemap tự động
                        </label>
                    </div>
                    <div class="form-text mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Tự động tạo sitemap.xml cho website để Google dễ dàng index
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-robot me-2"></i>
                        Robots.txt
                    </h6>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enable_robots_txt" id="enable_robots_txt"
                               <?= $additionalSettings['enable_robots_txt'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_robots_txt">
                            <i class="fas fa-file-alt me-1"></i>
                            Bật robots.txt tự động
                        </label>
                    </div>
                    <div class="form-text mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Tự động tạo robots.txt để hướng dẫn search engine crawl website
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEO Tips Section -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-lightbulb me-2"></i>
                Hướng dẫn SEO
            </h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Lưu ý SEO quan trọng:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>Meta Description:</strong> Nên dài 150-160 ký tự để hiển thị đầy đủ trên Google</li>
                    <li><strong>Google Analytics:</strong> Giúp theo dõi lưu lượng truy cập và hành vi người dùng</li>
                    <li><strong>Search Console:</strong> Theo dõi hiệu suất SEO và phát hiện lỗi crawl</li>
                    <li><strong>Facebook Pixel:</strong> Tối ưu quảng cáo Facebook và theo dõi chuyển đổi</li>
                    <li><strong>Sitemap:</strong> Giúp Google index trang web nhanh hơn và đầy đủ hơn</li>
                </ul>
            </div>
        </div>
    </div>
</div>
