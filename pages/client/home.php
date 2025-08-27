<?php
/**
 * Home Page - Glass Morphism UI
 * Tro365 - Website thuê trọ
 */

// Import required classes
use Tro365\Services\LocationService;
use Tro365\Core\Database;

// Get featured posts from database
$featuredPosts = [];
try {
    $db = Database::getInstance();
    
    // Get featured posts with related data
    $sql = "
        SELECT bd.*, kh.HoTen as NguoiDang, dm.TenDM as DanhMuc,
               (SELECT DuongDan FROM HinhAnhBaiDang WHERE BaiDangID = bd.ID ORDER BY ThuTu LIMIT 1) as AnhDaiDien
        FROM BaiDang bd
        LEFT JOIN KhachHang kh ON bd.NguoiDangID = kh.ID
        LEFT JOIN DanhMuc dm ON bd.DanhMucID = dm.ID
        WHERE bd.TrangThai = :status
        ORDER BY bd.LuotXem DESC, bd.NgayTao DESC
        LIMIT :limit
    ";
    
    $featuredPosts = $db->select($sql, [
        'status' => POST_STATUS_APPROVED,
        'limit' => 6
    ]);

    // Check favorite status for each post if user is logged in
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        foreach ($featuredPosts as &$post) {
            $favoriteResult = $db->selectOne(
                "SELECT ID FROM YeuThich WHERE KhachHangID = :userId AND BaiDangID = :postId",
                ['userId' => $userId, 'postId' => $post['ID']]
            );
            $post['isFavorited'] = $favoriteResult !== false;
        }
        unset($post); // Break reference
    } else {
        // Set all posts as not favorited for non-logged-in users
        foreach ($featuredPosts as &$post) {
            $post['isFavorited'] = false;
        }
        unset($post); // Break reference
    }
} catch (Exception $e) {
    error_log("Error fetching featured posts: " . $e->getMessage());
}

// Get statistics
$stats = [
    'total_posts' => 0,
    'total_users' => 0,
    'total_views' => 0
];

try {
    $db = Database::getInstance();
    
    // Get total approved posts
    $result = $db->selectOne("SELECT COUNT(*) as total FROM BaiDang WHERE TrangThai = :status", [
        'status' => POST_STATUS_APPROVED
    ]);
    $stats['total_posts'] = $result['total'] ?? 0;

    // Get total active users
    $result = $db->selectOne("SELECT COUNT(*) as total FROM KhachHang WHERE TrangThai = :status", [
        'status' => USER_STATUS_ACTIVE
    ]);
    $stats['total_users'] = $result['total'] ?? 0;

    // Get total views
    $result = $db->selectOne("SELECT SUM(LuotXem) as total FROM BaiDang");
    $stats['total_views'] = $result['total'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
}

// Set page variables for header
$pageTitle = 'Trang chủ'; // Header layout will automatically append " - " . getWebsiteName()
$pageDescription = getMetaDescription();
$pageKeywords = getMetaKeywords();

// Additional CSS for home page
$additionalCSS = ['/assets/css/client/main.css'];

// Include header
include __DIR__ . '/../../includes/layouts/client/header.php';
?>


<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">Tìm phòng trọ ưng ý</h1>
            <p class="hero-subtitle"><?= getWebsiteDescription() ?></p>

            <!-- Glass Search Form -->
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-8">
                    <div class="search-form">
                        <form action="/search" method="GET">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label text-white-50 small">Từ khóa</label>
                                    <input type="text" class="form-control" name="q" placeholder="Nhập từ khóa tìm kiếm...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-white-50 small">Địa điểm</label>
                                    <select class="form-select" name="province" id="home-province" aria-label="Chọn tỉnh thành">
                                        <option value="">Tất cả địa điểm</option>
                                        <!-- Provinces will be loaded via API -->
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn search-btn w-100">
                                        <i class="fas fa-search me-2"></i>Tìm kiếm
                                    </button>
                                </div>
                            </div>

                            <!-- Advanced Search Toggle -->
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-link text-white-50 text-decoration-none" data-bs-toggle="collapse" data-bs-target="#advancedSearch">
                                    <i class="fas fa-sliders-h me-1"></i> Tìm kiếm nâng cao
                                </button>
                            </div>

                            <!-- Advanced Search Panel -->
                            <div class="collapse mt-3" id="advancedSearch">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label text-white-50 small">Giá từ</label>
                                        <input type="number" class="form-control" name="price_from" placeholder="VNĐ" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-white-50 small">Giá đến</label>
                                        <input type="number" class="form-control" name="price_to" placeholder="VNĐ" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-white-50 small">Diện tích từ</label>
                                        <input type="number" class="form-control" name="area_from" placeholder="m²" min="0" step="0.1">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-white-50 small">Diện tích đến</label>
                                        <input type="number" class="form-control" name="area_to" placeholder="m²" min="0" step="0.1">
                                    </div>
                                </div>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-3">
                                        <label class="form-label text-white-50 small">Số phòng</label>
                                        <input type="number" class="form-control" name="rooms" placeholder="Số phòng" min="1" max="10">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-white-50 small">Danh mục</label>
                                        <select class="form-select" name="category">
                                            <option value="">Tất cả danh mục</option>
                                            <option value="1">Phòng trọ</option>
                                            <option value="2">Căn hộ mini</option>
                                            <option value="3">Nhà nguyên căn</option>
                                            <option value="4">Ký túc xá</option>
                                            <option value="5">Homestay</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-white-50 small">Sắp xếp</label>
                                        <select class="form-select" name="sort">
                                            <option value="newest">Mới nhất</option>
                                            <option value="oldest">Cũ nhất</option>
                                            <option value="price_asc">Giá thấp đến cao</option>
                                            <option value="price_desc">Giá cao đến thấp</option>
                                            <option value="area_asc">Diện tích nhỏ đến lớn</option>
                                            <option value="area_desc">Diện tích lớn đến nhỏ</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <!-- Empty column for layout balance -->
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Featured Posts Section -->
<?php if (!empty($featuredPosts)): ?>
<section class="py-5" style="background: var(--bg-secondary);">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col">
                <h2 class="fw-bold" style="color: var(--text-primary);">Phòng trọ nổi bật</h2>
                <p class="text-muted">Những phòng trọ được quan tâm nhiều nhất</p>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredPosts as $post): ?>
            <div class="col-lg-4 col-md-6"> <!-- Back to 3 columns layout as requested -->
                <div class="post-card">
                    <div class="position-relative">
                        <?php if ($post['AnhDaiDien']): ?>
                            <?php
                                $orig = htmlspecialchars($post['AnhDaiDien']);
                                $pi = pathinfo($post['AnhDaiDien']);
                                $thumbPath = ($pi['dirname'] ?? '') . '/thumb_' . ($pi['basename'] ?? '');
                                $thumbSrc = $orig;
                                $abs = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\') . $thumbPath;
                                if (!empty($pi['dirname']) && file_exists($abs)) {
                                    $thumbSrc = $thumbPath; // use thumbnail if exists
                                }
                            ?>
                            <img src="<?= $thumbSrc ?>"
                                 class="card-img-top"
                                 alt="<?= htmlspecialchars($post['TieuDe']) ?>"
                                 loading="lazy" decoding="async" referrerpolicy="no-referrer" fetchpriority="low">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 220px;">
                                <i class="fas fa-home fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Image Overlay -->
                        <div class="image-overlay"></div>

                        <!-- Category Badge -->
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="category-badge">
                                <?= htmlspecialchars($post['DanhMuc'] ?? 'Phòng trọ') ?>
                            </span>
                        </div>

                        <!-- Views Badge -->
                        <div class="position-absolute bottom-0 start-0 m-3">
                            <span class="views-badge">
                                <i class="fas fa-eye"></i>
                                <?= number_format($post['LuotXem']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="/post/<?= $post['ID'] ?>">
                                <?= htmlspecialchars($post['TieuDe']) ?>
                            </a>
                        </h5>

                        <div class="price-section">
                            <span class="price"><?= number_format($post['Gia']) ?> VNĐ/tháng</span>
                            <?php if ($post['DienTich']): ?>
                                <span class="area">
                                    <i class="fas fa-expand-arrows-alt me-1"></i>
                                    <?= $post['DienTich'] ?>m²
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-meta">
                            <div class="author">
                                <i class="fas fa-user"></i>
                                <?= htmlspecialchars($post['NguoiDang']) ?>
                            </div>
                            <div class="date">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?= date('d/m/Y', strtotime($post['NgayTao'])) ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="action-buttons">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn-favorite <?= $post['isFavorited'] ? 'favorited' : '' ?>"
                                        onclick="toggleFavorite(<?= $post['ID'] ?>, this)"
                                        title="<?= $post['isFavorited'] ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích' ?>">
                                    <i class="<?= $post['isFavorited'] ? 'fas' : 'far' ?> fa-heart <?= $post['isFavorited'] ? 'text-danger' : '' ?>"></i>
                                    <span class="d-none d-md-inline"><?= $post['isFavorited'] ? 'Đã yêu thích' : 'Yêu thích' ?></span>
                                </button>
                            <?php else: ?>
                                <button class="btn-favorite" onclick="redirectToLogin()" title="Đăng nhập để yêu thích">
                                    <i class="far fa-heart"></i>
                                    <span class="d-none d-md-inline">Yêu thích</span>
                                </button>
                            <?php endif; ?>
                            <a href="/post/<?= $post['ID'] ?>" class="btn-view">
                                <i class="fas fa-eye"></i>
                                Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="/search" class="btn-glass-primary btn-lg">
                <i class="fas fa-search me-2"></i>Xem tất cả phòng trọ
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col">
                <h2 class="fw-bold" style="color: var(--text-primary);">Tại sao chọn <?= getWebsiteName() ?>?</h2>
                <p class="text-muted">Chúng tôi cung cấp dịch vụ thuê trọ tốt nhất với nhiều ưu điểm vượt trội</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt text-white fa-2x"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-3" style="color: var(--text-primary);">Uy tín & An toàn</h3>
                        <p class="card-text text-muted">Tất cả thông tin được kiểm duyệt kỹ lưỡng, đảm bảo độ tin cậy cao cho người dùng</p>
                    </div>
                </div>
            </div>

            

            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-handshake text-white fa-2x"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-3" style="color: var(--text-primary);">Hỗ trợ 24/7</h3>
                        <p class="card-text text-muted">Đội ngũ hỗ trợ chuyên nghiệp luôn sẵn sàng giúp đỡ bạn mọi lúc mọi nơi</p>
                    </div>
                </div>
            </div>

            

            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="card-body text-center">
                        <div class="feature-icon">
                            <i class="fas fa-heart text-white fa-2x"></i>
                        </div>
                        <h3 class="card-title fw-bold mb-3" style="color: var(--text-primary);">Yêu thích & So sánh</h3>
                        <p class="card-text text-muted">Lưu các bài đăng yêu thích và so sánh để đưa ra quyết định tốt nhất</p>
                    </div>
                </div>
            </div>

            
        </div>
    </div>
</section>

<?php
// Get provinces data for featured provinces section
$locationService = new LocationService();
$allProvinces = $locationService->getProvinces();

// Featured provinces (major cities)
$featuredProvinces = [
    '79' => 'Hồ Chí Minh',
    '01' => 'Hà Nội',
    '48' => 'Đà Nẵng',
    '46' => 'Thừa Thiên Huế',
    '92' => 'Cần Thơ',
    '56' => 'Khánh Hòa'
];

// Province name to filename mapping (JPG images)
$provinceImageMap = [
    'Hồ Chí Minh' => 'hcm',
    'Hà Nội' => 'ha_noi',
    'Đà Nẵng' => 'da_nang',
    'Thừa Thiên Huế' => 'hue',
    'Cần Thơ' => 'can_tho',
    'Khánh Hòa' => 'khanh_hoa'
];

// Get post count by province
$postCountByProvince = [];
try {
    $sql = "SELECT TinhThanhID, COUNT(*) as SoLuong
            FROM BaiDang
            WHERE TrangThai = 1 AND TinhThanhID IS NOT NULL
            GROUP BY TinhThanhID
            ORDER BY SoLuong DESC
            LIMIT 12";
    $postCounts = $db->select($sql);

    foreach ($postCounts as $count) {
        $provinceName = $locationService->getProvinceName($count['TinhThanhID']);
        if ($provinceName) {
            $postCountByProvince[] = [
                'code' => $count['TinhThanhID'],
                'name' => $provinceName,
                'count' => $count['SoLuong']
            ];
        }
    }
} catch (Exception $e) {
    writeLog("Error getting post count by province: " . $e->getMessage());
    $postCountByProvince = [];
}
?>

<!-- Tỉnh, thành phố nổi bật Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-3" style="color: var(--text-primary);">Tỉnh, thành phố nổi bật</h2>
                <p class="text-muted">Khám phá các tỉnh thành có nhiều lựa chọn phòng trọ chất lượng</p>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredProvinces as $code => $name): ?>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="/search?province=<?= $code ?>" class="text-decoration-none">
                        <div class="province-card glass-card text-center h-100">
                            <div class="province-image-container">
                                <?php
                                $imageFileName = isset($provinceImageMap[$name]) ? $provinceImageMap[$name] : 'hcm';
                                ?>
                                <img data-src="/assets/images/provinces/<?= $imageFileName ?>.jpg"
                                     src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='225'%3E%3Crect width='100%25' height='100%25' fill='%23f8f9fa'/%3E%3C/svg%3E"
                                     alt="Hình ảnh đại diện <?= e($name) ?>"
                                     class="province-image"
                                     loading="lazy"
                                     data-fallback="/assets/images/provinces/hcm.jpg"
                                     onerror="this.src='/assets/images/provinces/hcm.jpg'">
                                <div class="province-overlay"></div>
                            </div>
                            <div class="province-content">
                                <h4 class="province-name"><?= e($name) ?></h4>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Khám phá thêm Trọ 365 ở các tỉnh thành Section -->
<section class="py-5" style="background: var(--bg-secondary);">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-3" style="color: var(--text-primary);">Khám phá thêm Trọ 365 ở các tỉnh thành</h2>
                <p style="color: var(--text-secondary);">Dưới đây là tổng hợp các tỉnh thành có nhiều trọ mới và được quan tâm nhất</p>
            </div>
        </div>

        <div class="row">
            <?php if (!empty($postCountByProvince)): ?>
                <?php foreach ($postCountByProvince as $province): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <a href="/search?province=<?= $province['code'] ?>" class="text-decoration-none">
                            <div class="province-stats-card glass-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="province-stats-name mb-0"><?= e($province['name']) ?></h4>
                                    <span class="province-stats-count"><?= number_format($province['count']) ?> phòng trọ</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">Đang cập nhật dữ liệu...</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Only show CTA Section if user is not logged in or has role < SELLER
$showCTA = !isLoggedIn() || !hasRole(ROLE_SELLER);
if ($showCTA):
?>
<!-- CTA Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="glass-card text-center">
                    <div class="feature-icon mx-auto mb-4">
                        <i class="fas fa-plus-circle text-white fa-2x"></i>
                    </div>
                    <h3 class="fw-bold mb-3" style="color: var(--text-primary);">Bạn có phòng trọ muốn cho thuê?</h3>
                    <p class="mb-4 text-muted">Đăng ký trở thành seller và bắt đầu kinh doanh ngay hôm nay. Hoa hồng chỉ 5% khi giao dịch thành công!</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="/register-seller" class="btn btn-primary btn-lg me-md-2">
                            <i class="fas fa-user-plus me-2"></i>Đăng ký Seller
                        </a>
                        <a href="/contact" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-phone me-2"></i>Liên hệ tư vấn
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>

<!-- Additional JavaScript for Home Page -->
<script src="/assets/js/common.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load provinces for home search
    window.Tro365Common.loadProvinces('home-province');

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            // Skip empty or invalid selectors
            if (!href || href === '#' || href.length <= 1) {
                return;
            }
            try {
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            } catch (error) {
                if (window.TRO365_DEBUG) {
                    console.warn('Invalid selector for smooth scrolling:', href, error);
                }
            }
        });
    });

    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe feature cards and glass cards
    document.querySelectorAll('.feature-card, .glass-card, .card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });

    // Search form enhancements
    const searchForm = document.querySelector('.search-form form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang tìm...';
                submitBtn.disabled = true;
            }
        });
    }

    // Auto-hide status badge after 5 seconds
    setTimeout(function() {
        const statusBadge = document.querySelector('.position-fixed .glass');
        if (statusBadge) {
            statusBadge.style.opacity = '0';
            statusBadge.style.transform = 'translateX(100%)';
            statusBadge.style.transition = 'all 0.3s ease';
        }
    }, 5000);

    if (window.TRO365_DEBUG) {
        console.log('Home page JavaScript initialized');
    }
});

// Global function for favorite toggle
function toggleFavorite(postId, buttonElement) {
    // Check if user is logged in
    <?php if (!isset($_SESSION['user_id'])): ?>
    alert('Vui lòng đăng nhập để sử dụng tính năng này');
    window.location.href = '/login';
    return;
    <?php else: ?>

    // Get button element if not provided
    if (!buttonElement) {
        buttonElement = event?.target?.closest('button');
    }

    if (!buttonElement) {
        console.error('Cannot find button element for favorite toggle');
        return;
    }

    // Prevent multiple clicks
    if (buttonElement.disabled) {
        return;
    }

    // Show loading state
    const heartIcon = buttonElement.querySelector('i');
    const textSpan = buttonElement.querySelector('span');
    const originalHeartClasses = heartIcon.className;
    const originalText = textSpan ? textSpan.textContent : '';

    // Set loading state
    buttonElement.disabled = true;
    heartIcon.className = 'fas fa-spinner fa-spin';
    if (textSpan) textSpan.textContent = 'Đang xử lý...';

    window.Tro365Common.toggleFavorite(postId, function(data) {
        // Restore button state
        buttonElement.disabled = false;

        if (data.success && data.data) {
            // Update UI based on API response
            if (data.data.favorited) {
                // Show filled red heart
                heartIcon.className = 'fas fa-heart text-danger';
                buttonElement.classList.add('favorited');
                buttonElement.title = 'Xóa khỏi yêu thích';
                if (textSpan) textSpan.textContent = 'Đã yêu thích';
            } else {
                // Show empty heart
                heartIcon.className = 'far fa-heart';
                buttonElement.classList.remove('favorited');
                buttonElement.title = 'Thêm vào yêu thích';
                if (textSpan) textSpan.textContent = 'Yêu thích';
            }

            // Update favorites count in navigation
            updateFavoritesCount(data.data.favorited);

            // Show success message briefly
            const msg = (data.data && data.data.message)
              || (data.data && data.data.favorited ? 'Đã thêm vào yêu thích' : 'Đã bỏ khỏi yêu thích');
            showToast(msg, 'success');
        } else {
            // Restore original state on error
            heartIcon.className = originalHeartClasses;
            if (textSpan) textSpan.textContent = originalText;

            // Show error message
            const errorMsg = data.message || 'Có lỗi xảy ra, vui lòng thử lại';
            showToast(errorMsg, 'error');
        }
    });
    <?php endif; ?>
}

// Function to redirect to login for non-logged-in users
function redirectToLogin() {
    alert('Vui lòng đăng nhập để sử dụng tính năng này');
    window.location.href = '/login';
}

// Function to update favorites count in navigation
function updateFavoritesCount(isAdded) {
    // Get current count from user dropdown
    const userDropdownCount = document.querySelector('.dropdown-item[href="/profile/favorites"] .item-count');
    const bottomNavBadge = document.querySelector('.bottom-nav-item[href="/profile/favorites"] .badge');
    const bottomNavItem = document.querySelector('.bottom-nav-item[href="/profile/favorites"]');

    // Get current count
    let currentCount = 0;
    if (userDropdownCount) {
        currentCount = parseInt(userDropdownCount.textContent.replace(/[^\d]/g, '')) || 0;
    } else if (bottomNavBadge) {
        const badgeText = bottomNavBadge.textContent.trim();
        currentCount = badgeText === '99+' ? 99 : (parseInt(badgeText) || 0);
    }

    // Update count
    const newCount = isAdded ? currentCount + 1 : Math.max(0, currentCount - 1);

    // Update user dropdown count
    if (userDropdownCount) {
        if (newCount > 0) {
            userDropdownCount.textContent = newCount.toLocaleString();
        } else {
            userDropdownCount.remove();
        }
    } else if (newCount > 0) {
        // Create count element if it doesn't exist
        const favoritesLink = document.querySelector('.dropdown-item[href="/profile/favorites"]');
        if (favoritesLink) {
            const countSpan = document.createElement('span');
            countSpan.className = 'item-count';
            countSpan.textContent = newCount.toLocaleString();
            favoritesLink.appendChild(countSpan);
        }
    }

    // Update bottom navigation badge
    if (bottomNavBadge) {
        if (newCount > 0) {
            bottomNavBadge.textContent = newCount > 99 ? '99+' : newCount.toString();
        } else {
            bottomNavBadge.remove();
        }
    } else if (newCount > 0 && bottomNavItem) {
        // Create badge if it doesn't exist
        const badge = document.createElement('span');
        badge.className = 'badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill';
        badge.style.fontSize = '0.6rem';
        badge.textContent = newCount > 99 ? '99+' : newCount.toString();
        bottomNavItem.appendChild(badge);
    }
}

// Toast notification (unified)
function showToast(message, type = 'info', duration = 3000) {
    if (window.TroToast && typeof window.TroToast.show === 'function') {
        window.TroToast.show({ message, type, duration });
    } else {
        // Fallback minimal alert
        alert(message);
    }
}
</script>

</body>
</html>
