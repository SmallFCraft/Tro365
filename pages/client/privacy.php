<?php
/**
 * Privacy Policy Page - Chính Sách Bảo Mật
 * Tro365 - Website thuê trọ
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions/helpers.php';
require_once __DIR__ . '/../../includes/functions/auth.php';

// Load system settings for dynamic content
$config = new \Tro365\Core\Config();
$siteSettings = $config->getSystemSettings();

$pageTitle = 'Chính Sách Bảo Mật';
$pageDescription = 'Chính sách bảo mật thông tin cá nhân của người dùng tại Tro365 - Nền tảng cho thuê phòng trọ uy tín tại Việt Nam.';
$pageKeywords = 'chính sách bảo mật, bảo vệ thông tin cá nhân, quyền riêng tư, Tro365, cho thuê phòng trọ';

$additionalCSS = [
    '/assets/css/client/glass-morphism.css',
    '/assets/css/components/common.css'
];

include __DIR__ . '/../../includes/layouts/client/header.php';
?>

<!-- Glass Morphism Hero Section -->
<section class="glass-hero">
    <div class="container">
        <div class="glass-hero-content">
            <div class="glass-icon-lg mx-auto mb-4">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="display-4 fw-bold mb-3">Chính Sách Bảo Mật</h1>
            <p class="lead mb-4">
                Cam kết bảo vệ thông tin cá nhân và quyền riêng tư của bạn<br>
                <?= e($siteSettings['ten_website']) ?> luôn đặt sự an toàn dữ liệu lên hàng đầu
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <span class="badge bg-success px-3 py-2 fs-6">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Cập nhật: <?= date('d/m/Y') ?>
                </span>
                <span class="badge bg-info px-3 py-2 fs-6">
                    <i class="fas fa-check-shield me-2"></i>
                    Tuân thủ GDPR
                </span>
            </div>
        </div>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
            <li class="breadcrumb-item active">Chính sách bảo mật</li>
        </ol>
    </nav>
</div>

<!-- Quick Navigation -->
<div class="container mb-5">
    <div class="glass-card">
        <h5 class="mb-3">
            <i class="fas fa-list me-2 text-primary"></i>
            Mục lục nhanh
        </h5>
        <div class="row quick-nav">
            <div class="col-md-6">
                <ul class="list-unstyled">
                    <li><a href="#section-1" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        1. Mục đích thu thập thông tin
                    </a></li>
                    <li><a href="#section-2" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        2. Phạm vi sử dụng thông tin
                    </a></li>
                    <li><a href="#section-3" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        3. Thời gian lưu trữ thông tin
                    </a></li>
                    <li><a href="#section-4" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        4. Quyền truy cập thông tin
                    </a></li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="list-unstyled">
                    <li><a href="#section-5" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        5. Thông tin liên hệ
                    </a></li>
                    <li><a href="#section-6" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        6. Quyền của người dùng
                    </a></li>
                    <li><a href="#section-7" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        7. Cam kết bảo mật
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <div class="privacy-content">
        
        <!-- Section 1: Purpose of Data Collection -->
        <section id="section-1" class="glass-panel mb-4">
            <h2 class="h4 mb-3">
                <span class="badge bg-primary me-2">1</span>
                Mục đích thu thập thông tin cá nhân
            </h2>
            <p class="mb-3">
                Việc thu thập dữ liệu chủ yếu trên nền tảng <strong><?= e($siteSettings['ten_website']) ?></strong> bao gồm: 
                email, điện thoại, tên đăng nhập, mật khẩu đăng nhập, địa chỉ khách hàng (thành viên).
            </p>
            
            <ul class="mb-3">
                <li>Đây là các thông tin mà <?= e($siteSettings['ten_website']) ?> cần thành viên cung cấp bắt buộc khi đăng ký sử dụng dịch vụ</li>
                <li>Để <?= e($siteSettings['ten_website']) ?> liên hệ xác nhận khi khách hàng đăng ký sử dụng dịch vụ trên website</li>
                <li>Nhằm đảm bảo quyền lợi cho người tiêu dùng và nâng cao chất lượng dịch vụ</li>
            </ul>

            <div class="alert alert-warning d-flex align-items-start">
                <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
                <div>
                    <strong>Trách nhiệm của thành viên:</strong> Các thành viên sẽ tự chịu trách nhiệm về bảo mật và lưu giữ mọi hoạt động sử dụng dịch vụ dưới tên đăng ký, mật khẩu và hộp thư điện tử của mình.
                </div>
            </div>
        </section>

        <!-- Section 2: Scope of Information Use -->
        <section id="section-2" class="glass-panel mb-4">
            <h2 class="h4 mb-3">
                <span class="badge bg-primary me-2">2</span>
                Phạm vi sử dụng thông tin
            </h2>
            <p class="mb-4"><?= e($siteSettings['ten_website']) ?> sử dụng thông tin cá nhân của thành viên cho các mục đích sau:</p>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="glass-container p-3">
                        <h6 class="mb-2"><i class="fas fa-cogs me-2"></i>Cung cấp dịch vụ</h6>
                        <small class="text-muted">Cung cấp các dịch vụ đến thành viên một cách tốt nhất</small>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="glass-container p-3">
                        <h6 class="mb-2"><i class="fas fa-bell me-2"></i>Gửi thông báo</h6>
                        <small class="text-muted">Gửi các thông báo về hoạt động trao đổi thông tin</small>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="glass-container p-3">
                        <h6 class="mb-2"><i class="fas fa-shield-alt me-2"></i>Bảo vệ tài khoản</h6>
                        <small class="text-muted">Ngăn ngừa các hoạt động phá hủy tài khoản hoặc giả mạo</small>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="glass-container p-3">
                        <h6 class="mb-2"><i class="fas fa-headset me-2"></i>Hỗ trợ khách hàng</h6>
                        <small class="text-muted">Liên lạc và giải quyết trong những trường hợp đặc biệt</small>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <strong>Cam kết:</strong> <?= e($siteSettings['ten_website']) ?> không sử dụng thông tin cá nhân ngoài mục đích xác nhận và liên hệ có liên quan đến giao dịch tại <?= e($siteSettings['ten_website']) ?>.
            </div>
        </section>

        <!-- Section 3: Data Storage Time -->
        <section id="section-3" class="glass-panel mb-4">
            <h2 class="h4 mb-3">
                <span class="badge bg-primary me-2">3</span>
                Thời gian lưu trữ thông tin
            </h2>
            
            <div class="glass-container p-4">
                <h6 class="mb-2">Chính sách lưu trữ</h6>
                <p class="mb-0">
                    Dữ liệu cá nhân của thành viên sẽ được lưu trữ cho đến khi có yêu cầu hủy bỏ 
                    hoặc tự thành viên đăng nhập và thực hiện hủy bỏ. Trong mọi trường hợp khác, 
                    thông tin cá nhân thành viên sẽ được bảo mật trên máy chủ của <?= e($siteSettings['ten_website']) ?>.
                </p>
            </div>
        </section>

        <!-- Section 4: Data Access Rights -->
        <section id="section-4" class="glass-panel mb-4">
            <h2 class="h4 mb-3">
                <span class="badge bg-primary me-2">4</span>
                Những người hoặc tổ chức có thể được tiếp cận với thông tin
            </h2>
            
            <div class="glass-container p-4 text-center">
                <div class="glass-icon-lg mx-auto mb-3">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h6>Quyền truy cập hạn chế</h6>
                <p class="mb-0">
                    Chỉ có <strong>cơ quan pháp luật</strong> (Viện kiểm sát, tòa án, cơ quan công an điều tra) 
                    mới có thể yêu cầu tiếp cận thông tin bảo mật của khách hàng khi có văn bản yêu cầu hợp pháp.
                </p>
            </div>
        </section>

        <!-- Section 5: Contact Information -->
        <section id="section-5" class="glass-panel mb-4">
            <h2 class="h4 mb-3">
                <span class="badge bg-primary me-2">5</span>
                Địa chỉ của đơn vị thu thập và quản lý thông tin
            </h2>
            
            <div class="glass-container p-4">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="fas fa-building me-2 text-primary"></i>Thông tin công ty</h6>
                        <p><strong>Tên công ty:</strong> <?= e($siteSettings['ten_website']) ?><br>
                        <strong>Website:</strong> <a href="https://<?= $_SERVER['HTTP_HOST'] ?>"><?= $_SERVER['HTTP_HOST'] ?></a></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3"><i class="fas fa-phone me-2 text-success"></i>Thông tin liên hệ</h6>
                        <p><strong>Số điện thoại:</strong> <a href="tel:<?= e($siteSettings['sdt_hotline']) ?>"><?= e($siteSettings['sdt_hotline']) ?></a><br>
                        <strong>Email:</strong> <a href="mailto:<?= e($siteSettings['email_lien_he']) ?>"><?= e($siteSettings['email_lien_he']) ?></a></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 6: User Rights -->
        <section id="section-6" class="glass-panel mb-4">
            <h2 class="h4 mb-3">
                <span class="badge bg-primary me-2">6</span>
                Phương thức chỉnh sửa dữ liệu cá nhân
            </h2>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="glass-container p-3 text-center">
                        <div class="glass-icon-sm mx-auto mb-2">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <h6>Tự chỉnh sửa</h6>
                        <p class="mb-3">Chỉnh sửa dữ liệu cá nhân bằng cách đăng nhập tài khoản</p>
                        <a href="/profile/edit" class="btn btn-glass btn-sm">
                            <i class="fas fa-edit me-2"></i>Chỉnh sửa hồ sơ
                        </a>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="glass-container p-3 text-center">
                        <div class="glass-icon-sm mx-auto mb-2">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h6>Liên hệ hỗ trợ</h6>
                        <p class="mb-3">Liên hệ bộ phận hỗ trợ để yêu cầu chỉnh sửa</p>
                        <small><strong>ĐT:</strong> <a href="tel:<?= e($siteSettings['sdt_hotline']) ?>"><?= e($siteSettings['sdt_hotline']) ?></a><br>
                        <strong>Email:</strong> <a href="mailto:<?= e($siteSettings['email_lien_he']) ?>"><?= e($siteSettings['email_lien_he']) ?></a></small>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 7: Privacy Commitment -->
        <section id="section-7" class="glass-panel mb-4">
            <h2 class="h4 mb-3">
                <span class="badge bg-primary me-2">7</span>
                Cam kết bảo mật thông tin cá nhân khách hàng
            </h2>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="glass-container p-3">
                        <h6 class="text-primary mb-2"><i class="fas fa-check-circle me-2"></i>Thu thập có đồng ý</h6>
                        <p class="mb-0 small">Việc thu thập và sử dụng thông tin chỉ được thực hiện khi có sự đồng ý của khách hàng.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="glass-container p-3">
                        <h6 class="text-primary mb-2"><i class="fas fa-ban me-2"></i>Không chia sẻ bên thứ 3</h6>
                        <p class="mb-0 small">Không chia sẻ thông tin cá nhân cho bên thứ ba khi không có sự đồng ý.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="glass-container p-3">
                        <h6 class="text-primary mb-2"><i class="fas fa-lock me-2"></i>Bảo mật giao dịch</h6>
                        <p class="mb-0 small">Bảo mật tuyệt đối mọi thông tin giao dịch trực tuyến của thành viên.</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="glass-container p-3">
                        <h6 class="text-primary mb-2"><i class="fas fa-shield-alt me-2"></i>Cam kết bảo mật</h6>
                        <p class="mb-0 small">Thông tin cá nhân được cam kết bảo mật tuyệt đối theo chính sách của <?= e($siteSettings['ten_website']) ?>.</p>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning mt-4">
                <strong>Yêu cầu thông tin chính xác:</strong> Thành viên phải cung cấp đầy đủ và chính xác thông tin cá nhân khi đăng ký. Tro365 không chịu trách nhiệm nếu thông tin cung cấp không chính xác.
            </div>
        </section>

    </div>
</div>

<!-- Support Section -->
<section class="glass-section">
    <div class="container">
        <div class="glass-card text-center">
            <h5 class="mb-3">Cần hỗ trợ về chính sách bảo mật?</h5>
            <p class="mb-4">Đội ngũ hỗ trợ của chúng tôi luôn sẵn sàng giải đáp mọi thắc mắc của bạn.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="tel:<?= e($siteSettings['sdt_hotline']) ?>" class="btn btn-glass">
                    <i class="fas fa-phone me-2"></i>
                    Gọi ngay
                </a>
                <a href="mailto:<?= e($siteSettings['email_lien_he']) ?>" class="btn btn-glass">
                    <i class="fas fa-envelope me-2"></i>
                    Gửi email
                </a>
                <a href="/contact" class="btn btn-glass">
                    <i class="fas fa-comments me-2"></i>
                    Liên hệ
                </a>
            </div>
        </div>
    </div>
</section>

<style>
/* Privacy Page Specific Styles */
.privacy-content section {
    scroll-margin-top: 2rem;
    opacity: 1;
    transform: translateY(0);
    animation: fadeInUp 0.6s ease forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.quick-nav a {
    transition: all 0.3s ease;
}

.quick-nav a:hover {
    color: var(--primary-color) !important;
    transform: translateX(5px);
}

@media (max-width: 768px) {
    .glass-hero-content h1 {
        font-size: 2.5rem;
    }
    
    .privacy-content {
        padding: 0;
    }
    
    .glass-panel {
        padding: 1.5rem;
    }
}

/* Enhanced Glass Morphism for Privacy Page */
.privacy-content .glass-container {
    transition: all 0.3s ease;
}

.privacy-content .glass-container:hover {
    transform: translateY(-2px);
    box-shadow: var(--glass-shadow-strong);
}

/* Ensure all sections are visible by default */
.privacy-content section {
    opacity: 1 !important;
    visibility: visible !important;
    transform: translateY(0) !important;
    display: block !important;
}
</style>

<script>
// Enhanced Privacy Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // CRITICAL: Ensure all sections are always visible (fallback system)
    const ensureAllSectionsVisible = () => {
        const sections = document.querySelectorAll('.privacy-content section');
        sections.forEach((section, index) => {
            section.style.opacity = '1';
            section.style.visibility = 'visible';
            section.style.transform = 'translateY(0)';
            section.style.display = 'block';
        });
    };

    // Run immediately
    ensureAllSectionsVisible();
    
    // Run after a short delay
    setTimeout(ensureAllSectionsVisible, 100);
    
    // Run periodically as a safety measure
    setInterval(ensureAllSectionsVisible, 2000);

    // Intersection Observer for animations (enhancement only, not required for visibility)
    if ('IntersectionObserver' in window) {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = '0s';
                    entry.target.classList.add('animate');
                }
            });
        }, observerOptions);

        // Observe all sections
        document.querySelectorAll('.privacy-content section').forEach(section => {
            observer.observe(section);
        });
    }

    // Active navigation highlighting
    const sections = document.querySelectorAll('.privacy-content section');
    const navLinks = document.querySelectorAll('.quick-nav a');

    const highlightNav = () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollY >= (sectionTop - 100)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    };

    window.addEventListener('scroll', highlightNav);
});
</script>

<?php
// Include footer
include __DIR__ . '/../../includes/layouts/client/footer.php';
?>