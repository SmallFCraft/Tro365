<?php
/**
 * About Page - Modern 2025-2026 Design
 * Tro365 - Website thuê trọ
 */

// Set page variables for header
$pageTitle = 'Giới thiệu';
$pageDescription = 'Tìm hiểu về Trọ 365 - nền tảng thuê trọ uy tín hàng đầu Việt Nam. Kết nối hàng triệu người tìm nhà với chủ nhà trên toàn quốc.';

// Additional CSS for about page
$additionalCSS = ['/assets/css/client/about.css'];

// Include header
include __DIR__ . '/../../includes/layouts/client/header.php';
?>

<!-- Include About Page CSS -->
<link rel="stylesheet" href="/assets/css/client/about.css">

<!-- Hero Section with Modern Design -->
<section class="about-hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Trọ 365</h1>
            <p class="hero-subtitle">Nền tảng thuê trọ thông minh hàng đầu Việt Nam<br>Kết nối hàng triệu người tìm nhà với chủ nhà uy tín</p>

            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number" data-count="1000000">0</span>
                    <span class="stat-label">Người dùng</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-count="50000">0</span>
                    <span class="stat-label">Bài đăng/tháng</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-count="63">0</span>
                    <span class="stat-label">Tỉnh thành</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-count="99">0</span>
                    <span class="stat-label">% Hài lòng</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
            <li class="breadcrumb-item active">Giới thiệu</li>
        </ol>
    </nav>
</div>

<!-- Mission & Vision with Glass Morphism -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="glass-section">
                    <div class="feature-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3 class="text-center mb-3 text-primary">Sứ mệnh</h3>
                    <p class="text-center">
                        Tạo ra một nền tảng thuê trọ minh bạch, uy tín và tiện lợi nhất,
                        giúp mọi người dễ dàng tìm được nơi ở phù hợp với nhu cầu và tài chính của mình.
                        Chúng tôi cam kết mang đến trải nghiệm tìm kiếm nhà trọ tốt nhất với công nghệ hiện đại.
                    </p>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="glass-section">
                    <div class="feature-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="text-center mb-3 text-primary">Tầm nhìn</h3>
                    <p class="text-center">
                        Trở thành nền tảng thuê trọ số 1 Việt Nam, được tin tưởng bởi
                        hàng triệu người dùng và đóng góp tích cực vào sự phát triển của thị trường bất động sản.
                        Xây dựng một cộng đồng kết nối bền vững giữa người thuê và chủ nhà.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us - Interactive Features -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Tại sao chọn Trọ 365?</h2>
            <p class="lead text-muted">Những ưu điểm vượt trội khiến chúng tôi trở thành lựa chọn hàng đầu</p>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4 class="mb-3">Uy tín & An toàn</h4>
                <p>Tất cả thông tin được kiểm duyệt kỹ lưỡng. Hệ thống bảo mật cao cấp bảo vệ thông tin cá nhân của bạn với công nghệ mã hóa tiên tiến.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-search-plus"></i>
                </div>
                <h4 class="mb-3">Tìm kiếm thông minh</h4>
                <p>Công cụ tìm kiếm AI-powered với hơn 20 bộ lọc thông minh, giúp bạn tìm được phòng trọ phù hợp chỉ trong vài phút.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h4 class="mb-3">Giao diện hiện đại</h4>
                <p>Thiết kế responsive với Glass Morphism UI, tối ưu cho mọi thiết bị. Trải nghiệm người dùng mượt mà và trực quan.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h4 class="mb-3">Hỗ trợ 24/7</h4>
                <p>Đội ngũ chăm sóc khách hàng chuyên nghiệp, sẵn sàng hỗ trợ bạn mọi lúc mọi nơi qua nhiều kênh liên lạc.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4 class="mb-3">Thống kê chi tiết</h4>
                <p>Dashboard analytics chuyên nghiệp cho chủ nhà với báo cáo real-time, giúp tối ưu hóa hiệu quả kinh doanh.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h4 class="mb-3">Kết nối nhanh chóng</h4>
                <p>Hệ thống chat real-time và video call tích hợp, kết nối trực tiếp giữa người thuê và chủ nhà một cách nhanh chóng.</p>
            </div>
        </div>
    </div>
</section>
<!-- Interactive Timeline -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Hành trình phát triển</h2>
            <p class="lead text-muted">Từ ý tưởng đến nền tảng hàng đầu Việt Nam</p>
        </div>

        <div class="timeline-container">
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-content">
                        <h5 class="fw-bold text-primary">2023 - Khởi đầu</h5>
                        <p>Ý tưởng về một nền tảng thuê trọ minh bạch và tiện lợi được hình thành. Nghiên cứu thị trường và phát triển MVP đầu tiên.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-content">
                        <h5 class="fw-bold text-primary">Q1/2024 - Ra mắt Beta</h5>
                        <p>Phiên bản thử nghiệm được ra mắt với 1,000 người dùng đầu tiên tại Hà Nội và TP.HCM. Thu thập feedback và cải thiện sản phẩm.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-content">
                        <h5 class="fw-bold text-primary">Q3/2024 - Mở rộng</h5>
                        <p>Mở rộng ra 15 tỉnh thành lớn với hơn 25,000 bài đăng. Tích hợp AI search và hệ thống đánh giá uy tín.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-content">
                        <h5 class="fw-bold text-primary">2025 - Toàn quốc</h5>
                        <p>Phủ sóng toàn quốc 63 tỉnh thành với hơn 1 triệu người dùng tin tưởng. Ra mắt ứng dụng mobile và tính năng VR tour.</p>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Team Section with Modern Design -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Đội ngũ của chúng tôi</h2>
            <p class="lead text-muted">Những con người tài năng đằng sau thành công của Trọ 365</p>
        </div>

        <div class="team-grid">
            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&h=300&fit=crop&crop=face"
                     alt="CEO" class="team-avatar">
                <h5 class="fw-bold mb-2">Phạm Văn Huy</h5>
                <p class="text-primary mb-2">CEO & Founder</p>
                <p class="text-muted mb-3">10+ năm kinh nghiệm trong lĩnh vực bất động sản và công nghệ. Tốt nghiệp MBA tại ĐH Kinh tế Quốc dân.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-linkedin"></i>
                    </a>
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>

            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=300&h=300&fit=crop&crop=face"
                     alt="CTO" class="team-avatar">
                <h5 class="fw-bold mb-2">Nguyễn Thị Lan</h5>
                <p class="text-primary mb-2">CTO</p>
                <p class="text-muted mb-3">Chuyên gia công nghệ với 8+ năm kinh nghiệm phát triển sản phẩm. Chuyên về AI/ML và kiến trúc hệ thống.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-github"></i>
                    </a>
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-linkedin"></i>
                    </a>
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>

            <div class="team-card">
                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face"
                     alt="CMO" class="team-avatar">
                <h5 class="fw-bold mb-2">Trần Minh Đức</h5>
                <p class="text-primary mb-2">CMO</p>
                <p class="text-muted mb-3">Chuyên gia marketing với kinh nghiệm xây dựng thương hiệu mạnh. Từng làm việc tại các startup unicorn.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-linkedin"></i>
                    </a>
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Call to Action Section -->
<section class="about-hero" style="min-height: 60vh;">
    <div class="container">
        <div class="hero-content">
            <h2 class="display-4 fw-bold mb-4">Sẵn sàng tìm nhà mới?</h2>
            <p class="lead mb-4">Tham gia cộng đồng hàng triệu người dùng đang tin tưởng Trọ 365<br>Trải nghiệm tìm kiếm nhà trọ thông minh và hiện đại nhất</p>

            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="/search" class="btn btn-glass-primary btn-lg">
                    <i class="fas fa-search me-2"></i>
                    Tìm phòng trọ ngay
                </a>
                <a href="/register-seller" class="btn btn-glass-secondary btn-lg">
                    <i class="fas fa-user-plus me-2"></i>
                    Trở thành Seller
                </a>
            </div>
        </div>
    </div>
</section>
<!-- JavaScript for Interactive Features -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate counter numbers
    function animateCounters() {
        const counters = document.querySelectorAll('.stat-number[data-count]');

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const duration = 2000; // 2 seconds
            const increment = target / (duration / 16); // 60fps
            let current = 0;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }

                // Format number with appropriate suffix
                let displayValue = Math.floor(current);
                if (target >= 1000000) {
                    displayValue = (current / 1000000).toFixed(1) + 'M+';
                } else if (target >= 1000) {
                    displayValue = (current / 1000).toFixed(0) + 'K+';
                } else if (target === 99) {
                    displayValue = Math.floor(current) + '%';
                } else {
                    displayValue = Math.floor(current);
                }

                counter.textContent = displayValue;
            }, 16);
        });
    }

    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.3,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                if (entry.target.classList.contains('hero-stats')) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }

                // Add animation classes
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe elements for animation
    const animatedElements = document.querySelectorAll('.glass-section, .feature-card, .team-card, .timeline-item');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease-out';
        observer.observe(el);
    });

    // Observe hero stats
    const heroStats = document.querySelector('.hero-stats');
    if (heroStats) {
        observer.observe(heroStats);
    }

    // Smooth scrolling for anchor links
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

    // Add hover effects to feature cards
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>
