<?php
/**
 * Client Footer Layout
 * Tro365 - Website thuê trọ
 */
?>

<!-- Footer -->
<footer class="tro365-footer">

    <!-- Main Footer Content -->
    <div class="footer-main">
        <div class="container">
            <div class="row">
                <!-- Company Info -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="footer-brand">
                        <h5 class="brand-title">
                            <i class="fas fa-home me-2"></i>
                            <?= getWebsiteName() ?>
                        </h5>
                        <p class="brand-description">
                            <?= getWebsiteDescription() ?>
                        </p>

                        <!-- Social Media -->
                        <div class="social-links">
                            <h6 class="social-title">Kết nối với chúng tôi</h6>
                            <div class="social-icons">
                                <?php $facebookUrl = getCompanyInfo('facebook_url', ''); ?>
                                <?php if ($facebookUrl): ?>
                                    <a href="<?= $facebookUrl ?>" class="social-link facebook" target="_blank" title="Facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                <?php endif; ?>
                                <?php $zaloUrl = getCompanyInfo('zalo_url', ''); ?>
                                <?php if ($zaloUrl): ?>
                                    <a href="<?= $zaloUrl ?>" class="social-link zalo" target="_blank" title="Zalo">
                                        <i class="fas fa-comment-dots"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (defined('YOUTUBE_URL') && YOUTUBE_URL): ?>
                                    <a href="<?= YOUTUBE_URL ?>" class="social-link youtube" target="_blank" title="YouTube">
                                        <i class="fab fa-youtube"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (defined('INSTAGRAM_URL') && INSTAGRAM_URL): ?>
                                    <a href="<?= INSTAGRAM_URL ?>" class="social-link instagram" target="_blank" title="Instagram">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (defined('TIKTOK_URL') && TIKTOK_URL): ?>
                                    <a href="<?= TIKTOK_URL ?>" class="social-link tiktok" target="_blank" title="TikTok">
                                        <i class="fab fa-tiktok"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-section">
                        <h6 class="section-title">Danh mục</h6>
                        <ul class="footer-links">
                            <li><a href="/search?category=1">
                                <i class="fas fa-bed me-2"></i>Phòng trọ
                            </a></li>
                            <li><a href="/search?category=2">
                                <i class="fas fa-building me-2"></i>Căn hộ mini
                            </a></li>
                            <li><a href="/search?category=3">
                                <i class="fas fa-home me-2"></i>Nhà nguyên căn
                            </a></li>
                            <li><a href="/search?category=4">
                                <i class="fas fa-school me-2"></i>Ký túc xá
                            </a></li>
                            <li><a href="/search?category=5">
                                <i class="fas fa-heart me-2"></i>Homestay
                            </a></li>
                        </ul>
                    </div>
                </div>

                <!-- Support Links -->
                <div class="col-lg-2 col-md-6 mb-4">
                    <div class="footer-section">
                        <h6 class="section-title">Hỗ trợ</h6>
                        <ul class="footer-links">
                            <li><a href="/about">
                                <i class="fas fa-info-circle me-2"></i>Giới thiệu
                            </a></li>
                            <li><a href="/contact">
                                <i class="fas fa-envelope me-2"></i>Liên hệ
                            </a></li>
                            <li><a href="/help">
                                <i class="fas fa-question-circle me-2"></i>Hướng dẫn
                            </a></li>
                            <li><a href="/terms">
                                <i class="fas fa-file-contract me-2"></i>Điều khoản
                            </a></li>
                            <li><a href="/privacy">
                                <i class="fas fa-shield-alt me-2"></i>Bảo mật
                            </a></li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-4 col-md-12 mb-4">
                    <div class="footer-section">
                        <h6 class="section-title">Thông tin liên hệ</h6>
                        <div class="contact-info">
                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="contact-content">
                                    <strong>Địa chỉ:</strong><br>
                                    <?= getCompanyInfo('dia_chi_cong_ty', 'Hà Nội, Việt Nam') ?>
                                </div>
                            </div>

                    

                            <div class="contact-item">
                                <div class="contact-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="contact-content">
                                    <strong>Email:</strong><br>
                                    <a href="mailto:<?= getCompanyInfo('email_lien_he', 'contact@tro365.com') ?>" class="contact-link">
                                        <?= getCompanyInfo('email_lien_he', 'contact@tro365.com') ?>
                                    </a>
                                </div>
                            </div>

                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-8 mb-3 mb-lg-0">
                    <div class="copyright text-center">
                        <p class="mb-0">
                            &copy; <?= date('Y') ?> <?= getCompanyInfo('ten_cong_ty', getWebsiteName()) ?>.
                            Tất cả quyền được bảo lưu.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-4">
                    <div class="footer-meta">
                        <div class="footer-links-inline">
                            <span class="version">v<?= getAppVersion() ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Floating Action Buttons -->
<div class="floating-actions">
    <!-- Back to Top -->
    <button type="button" class="fab-btn fab-back-to-top" id="backToTop" title="Lên đầu trang">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Quick Search -->
    <button type="button" class="fab-btn fab-search" id="quickSearch" title="Tìm kiếm nhanh">
        <i class="fas fa-search"></i>
    </button>
</div>

<!-- Quick Search Modal -->
<div class="modal fade" id="quickSearchModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-modal">
            <div class="modal-header glass-header">
                <h5 class="modal-title">
                    <i class="fas fa-search me-2 text-primary"></i>
                    Tìm kiếm nhanh
                </h5>
                <button type="button" class="btn-close glass-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body glass-body">
                <form id="quickSearchForm">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="glass-form-group">
                                <label class="glass-label">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    Khu vực
                                </label>
                                <select class="glass-select" name="province" id="quick-search-province">
                                    <option value="">🌍 Chọn tỉnh/thành</option>
                                    <!-- Provinces will be loaded via API -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="glass-form-group">
                                <label class="glass-label">
                                    <i class="fas fa-home me-2"></i>
                                    Loại phòng
                                </label>
                                <select class="glass-select" name="category" id="quick-search-category">
                                    <option value="">🏠 Tất cả loại phòng</option>
                                    <?php
                                    // Load categories from database
                                    try {
                                        $categoryModel = new \Tro365\Models\Category();
                                        $categories = $categoryModel->getAllActive();
                                        foreach ($categories as $cat) {
                                            echo '<option value="' . $cat['ID'] . '">' . htmlspecialchars($cat['TenDM']) . '</option>';
                                        }
                                    } catch (Exception $e) {
                                        // Fallback to static options
                                        echo '<option value="1">Phòng trọ</option>';
                                        echo '<option value="2">Căn hộ mini</option>';
                                        echo '<option value="3">Nhà nguyên căn</option>';
                                        echo '<option value="4">Ký túc xá</option>';
                                        echo '<option value="5">Homestay</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="glass-form-group">
                                <label class="glass-label">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    Giá từ
                                </label>
                                <select class="glass-select" name="price_from">
                                    <option value="">💰 Không giới hạn</option>
                                    <option value="500000">500K</option>
                                    <option value="1000000">1 triệu</option>
                                    <option value="2000000">2 triệu</option>
                                    <option value="3000000">3 triệu</option>
                                    <option value="5000000">5 triệu</option>
                                    <option value="10000000">10 triệu</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="glass-form-group">
                                <label class="glass-label">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    Giá đến
                                </label>
                                <select class="glass-select" name="price_to">
                                    <option value="">💰 Không giới hạn</option>
                                    <option value="1000000">1 triệu</option>
                                    <option value="2000000">2 triệu</option>
                                    <option value="3000000">3 triệu</option>
                                    <option value="5000000">5 triệu</option>
                                    <option value="10000000">10 triệu</option>
                                    <option value="20000000">20 triệu</option>
                                    <option value="50000000">50 triệu</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="glass-form-group">
                                <label class="glass-label">
                                    <i class="fas fa-keyboard me-2"></i>
                                    Từ khóa
                                </label>
                                <input type="text" class="glass-input" name="keyword" placeholder="🔍 Nhập từ khóa tìm kiếm...">
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="glass-btn glass-btn-primary">
                            <i class="fas fa-search me-2"></i>
                            Tìm kiếm ngay
                            <span class="glass-btn-shine"></span>
                        </button>
                        <button type="button" class="glass-btn glass-btn-secondary ms-3" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>
                            Đóng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modern JavaScript Libraries (AssetManager) -->
<?php
$am = new \Tro365\Assets\AssetManager(app_url(''));
echo $am->renderFooter();
?>

<!-- Bootstrap JS - Deferred (kept) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>

<!-- jQuery - Moved to async, load after paint if possible -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" async></script>

<!-- Footer CSS is already included in layouts.css -->

<!-- Common JavaScript Functions - Deferred for Performance -->
<script src="<?= app_url('assets/js/common.js') ?>" defer></script>

<!-- Footer JavaScript - Deferred for Performance -->
<script src="<?= app_url('assets/js/client/footer.js') ?>" defer></script>

<!-- Additional JS for specific pages -->
<?php if (isset($additionalJS)): ?>
    <?php foreach ($additionalJS as $js): ?>
        <script src="<?= $js ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Custom JS -->
<?php if (isset($customJS)): ?>
    <script><?= $customJS ?></script>
<?php endif; ?>

<script>
// Global Configuration
window.Tro365Config = {
    csrfToken: '<?= $_SESSION['csrf_token'] ?? '' ?>',
    userRole: <?= isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'null' ?>,
    appUrl: '<?= app_url() ?>',
    isLoggedIn: <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>
};

// Set global variables for session refresh
window.currentUserRole = <?= isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'null' ?>;
window.currentUserStatus = <?= isset($_SESSION['user_status']) ? $_SESSION['user_status'] : 'null' ?>;

// Auto-dismiss dismissible alerts only (notification alerts, not content alerts)
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert.alert-dismissible');
    alerts.forEach(function(alert) {
        if (bootstrap.Alert.getOrCreateInstance) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }
    });
}, 5000);

// Set CSRF token for all AJAX requests
if (window.jQuery) {
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                xhr.setRequestHeader("X-CSRF-Token", window.Tro365Config.csrfToken);
            }
        }
    });
}

// Load provinces for quick search modal with event-based caching
document.addEventListener('DOMContentLoaded', function() {
    const quickSearchProvince = document.getElementById('quick-search-province');

    if (quickSearchProvince) {
        // Check if data is already cached
        if (window.Tro365Common && window.Tro365Common._cache && window.Tro365Common._cache.provinces) {
            populateQuickSearchProvinces(window.Tro365Common._cache.provinces);
        } else {
            // Listen for preloaded data
            window.addEventListener('provincesLoaded', function(event) {
                populateQuickSearchProvinces(event.detail);
            });
        }
    }

    function populateQuickSearchProvinces(provinces) {
        provinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.ID;
            option.textContent = province.TenTT;
            quickSearchProvince.appendChild(option);
        });
    }
});
</script>

<?php if (isset($_SESSION['user_role'])): ?>
<!-- Session Auto-Refresh for all authenticated users - Deferred for Performance -->
<script src="/assets/js/session-refresh.js" defer></script>
<?php endif; ?>

<?php
// Render Debug Panel for client pages
if (isDebugModeEnabled()) {
    $debugManager = \Tro365\DebugManager::getInstance();
    echo $debugManager->renderDebugPanel();
}
?>

</body>
</html>
