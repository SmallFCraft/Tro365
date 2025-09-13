<?php
/**
 * Terms & Conditions Page - Điều Khoản Sử Dụng
 * Tro365 - Website thuê trọ
 * Modern Glass Morphism Design with Vietnamese Content
 */

// Load configuration and dependencies
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions/helpers.php';
require_once __DIR__ . '/../../includes/functions/auth.php';

// Performance optimization includes
require_once __DIR__ . '/../../includes/performance/optimization.php';

// Use performance optimization service
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

// Load system settings for dynamic content
$config = new \Tro365\Core\Config();
$siteSettings = $config->getSystemSettings();

// Set page variables for header
$pageTitle = 'Điều Khoản Sử Dụng';
$pageDescription = 'Điều khoản và điều kiện sử dụng dịch vụ của Tro365 - Nền tảng cho thuê phòng trọ uy tín tại Việt Nam. Quy định về quyền và trách nhiệm của người dùng.';
$pageKeywords = 'điều khoản sử dụng, quy định, chính sách, Tro365, cho thuê phòng trọ, trách nhiệm người dùng';

// Additional CSS for terms page
$additionalCSS = [
    '/assets/css/client/glass-morphism.css',
    '/assets/css/components/common.css'
];

// Include header
include __DIR__ . '/../../includes/layouts/client/header.php';
?>

<!-- Glass Morphism Hero Section -->
<section class="glass-hero">
    <div class="container">
        <div class="glass-hero-content">
            <div class="glass-icon-lg mx-auto mb-4">
                <i class="fas fa-file-contract"></i>
            </div>
            <h1 class="display-4 fw-bold mb-3">Điều Khoản Sử Dụng</h1>
            <p class="lead mb-4">
                Quy định và điều kiện sử dụng dịch vụ của <?= e($siteSettings['ten_website']) ?><br>
                Vui lòng đọc kỹ trước khi sử dụng dịch vụ
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <span class="badge bg-primary px-3 py-2 fs-6">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Cập nhật: <?= date('d/m/Y') ?>
                </span>
                <span class="badge bg-success px-3 py-2 fs-6">
                    <i class="fas fa-check-circle me-2"></i>
                    Phiên bản 2.0
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
            <li class="breadcrumb-item active">Điều khoản sử dụng</li>
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
                        1. Chấp nhận điều khoản
                    </a></li>
                    <li><a href="#section-2" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        2. Giới thiệu dịch vụ
                    </a></li>
                    <li><a href="#section-3" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        3. Đăng ký tài khoản
                    </a></li>
                    <li><a href="#section-4" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        4. Quyền và nghĩa vụ
                    </a></li>
                    <li><a href="#section-5" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        5. Quy định đăng tin
                    </a></li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="list-unstyled">
                    <li><a href="#section-6" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        6. Thanh toán và phí dịch vụ
                    </a></li>
                    <li><a href="#section-7" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        7. Bảo mật thông tin
                    </a></li>
                    <li><a href="#section-8" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        8. Trách nhiệm pháp lý
                    </a></li>
                    <li><a href="#section-9" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        9. Sửa đổi điều khoản
                    </a></li>
                    <li><a href="#section-10" class="text-decoration-none d-flex align-items-center py-1">
                        <i class="fas fa-chevron-right me-2 text-primary"></i>
                        10. Liên hệ hỗ trợ
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <div class="glass-grid-2">
        <!-- Main Content -->
        <div class="terms-content">
            
            <!-- Section 1: Acceptance of Terms -->
            <section id="section-1" class="glass-panel mb-4">
                <h2 class="h4 mb-3">
                    <span class="badge bg-primary me-2">1</span>
                    Chấp nhận điều khoản
                </h2>
                <p class="mb-3">
                    Khi truy cập và sử dụng website <strong><?= e($siteSettings['ten_website']) ?></strong> (sau đây gọi là "Website") 
                    và các dịch vụ liên quan, bạn đồng ý tuân thủ và bị ràng buộc bởi các điều khoản 
                    và điều kiện được nêu trong văn bản này.
                </p>
                <div class="alert alert-info d-flex align-items-start">
                    <i class="fas fa-info-circle me-3 mt-1"></i>
                    <div>
                        <strong>Lưu ý quan trọng:</strong> Nếu bạn không đồng ý với bất kỳ điều khoản nào, 
                        vui lòng ngừng sử dụng dịch vụ ngay lập tức.
                    </div>
                </div>
            </section>

            <!-- Section 2: Service Description -->
            <section id="section-2" class="glass-panel mb-4">
                <h2 class="h4 mb-3">
                    <span class="badge bg-primary me-2">2</span>
                    Giới thiệu dịch vụ
                </h2>
                <p class="mb-3">
                    <?= e($siteSettings['ten_website']) ?> là nền tảng trực tuyến kết nối những người có nhu cầu tìm kiếm chỗ ở với 
                    những người có phòng trọ, nhà trọ cho thuê tại Việt Nam.
                </p>
                <h5 class="mb-3">Dịch vụ chính bao gồm:</h5>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <div class="glass-container p-3">
                            <div class="d-flex align-items-center">
                                <div class="glass-icon-sm me-3">
                                    <i class="fas fa-search"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Tìm kiếm phòng trọ</h6>
                                    <small class="text-muted">Công cụ tìm kiếm thông minh</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-container p-3">
                            <div class="d-flex align-items-center">
                                <div class="glass-icon-sm me-3">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Đăng tin cho thuê</h6>
                                    <small class="text-muted">Quản lý bài đăng hiệu quả</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-container p-3">
                            <div class="d-flex align-items-center">
                                <div class="glass-icon-sm me-3">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Liên hệ trực tiếp</h6>
                                    <small class="text-muted">Kết nối nhanh chóng</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-container p-3">
                            <div class="d-flex align-items-center">
                                <div class="glass-icon-sm me-3">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Bảo mật thông tin</h6>
                                    <small class="text-muted">An toàn và tin cậy</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 3: Account Registration -->
            <section id="section-3" class="glass-panel mb-4">
                <h2 class="h4 mb-3">
                    <span class="badge bg-primary me-2">3</span>
                    Đăng ký tài khoản
                </h2>
                <h5 class="mb-3">Yêu cầu đăng ký:</h5>
                <ul class="list-unstyled">
                    <li class="d-flex align-items-start mb-2">
                        <i class="fas fa-check text-success me-3 mt-1"></i>
                        Bạn phải đủ 18 tuổi hoặc có sự đồng ý của người giám hộ hợp pháp
                    </li>
                    <li class="d-flex align-items-start mb-2">
                        <i class="fas fa-check text-success me-3 mt-1"></i>
                        Cung cấp thông tin chính xác, đầy đủ và cập nhật
                    </li>
                    <li class="d-flex align-items-start mb-2">
                        <i class="fas fa-check text-success me-3 mt-1"></i>
                        Sử dụng email và số điện thoại hợp lệ để xác thực
                    </li>
                    <li class="d-flex align-items-start mb-2">
                        <i class="fas fa-check text-success me-3 mt-1"></i>
                        Không tạo nhiều tài khoản để lạm dụng dịch vụ
                    </li>
                </ul>
                
                <div class="alert alert-warning d-flex align-items-start mt-3">
                    <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
                    <div>
                        <strong>Trách nhiệm bảo mật:</strong> Bạn có trách nhiệm bảo mật thông tin đăng nhập 
                        và thông báo ngay cho chúng tôi nếu phát hiện tài khoản bị sử dụng trái phép.
                    </div>
                </div>
            </section>

            <!-- Section 4: Rights and Obligations -->
            <section id="section-4" class="glass-panel mb-4">
                <h2 class="h4 mb-3">
                    <span class="badge bg-primary me-2">4</span>
                    Quyền và nghĩa vụ của người dùng
                </h2>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-success mb-3">
                            <i class="fas fa-user-check me-2"></i>
                            Quyền của người dùng
                        </h5>
                        <ul class="list-unstyled">
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-check text-success me-3 mt-1"></i>
                                Sử dụng các tính năng của website miễn phí
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-check text-success me-3 mt-1"></i>
                                Đăng tin cho thuê nhà trọ theo quy định
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-check text-success me-3 mt-1"></i>
                                Tìm kiếm và liên hệ với chủ nhà
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-check text-success me-3 mt-1"></i>
                                Được bảo vệ thông tin cá nhân
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-check text-success me-3 mt-1"></i>
                                Được hỗ trợ kỹ thuật khi cần thiết
                            </li>
                        </ul>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="text-danger mb-3">
                            <i class="fas fa-user-times me-2"></i>
                            Nghĩa vụ của người dùng
                        </h5>
                        <ul class="list-unstyled">
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-times text-danger me-3 mt-1"></i>
                                Không đăng thông tin sai lệch, gian lận
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-times text-danger me-3 mt-1"></i>
                                Không sử dụng ngôn ngữ phản cảm, xúc phạm
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-times text-danger me-3 mt-1"></i>
                                Không spam hoặc gửi thông tin rác
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-times text-danger me-3 mt-1"></i>
                                Không vi phạm pháp luật Việt Nam
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-times text-danger me-3 mt-1"></i>
                                Không can thiệp vào hệ thống kỹ thuật
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Section 5: Posting Rules -->
            <section id="section-5" class="glass-panel mb-4">
                <h2 class="h4 mb-3">
                    <span class="badge bg-primary me-2">5</span>
                    Quy định đăng tin
                </h2>
                
                <h5 class="mb-3">Yêu cầu về nội dung:</h5>
                <div class="glass-container p-3 mb-3">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="glass-icon mx-auto mb-2">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <h6>Hình ảnh thật</h6>
                                <small class="text-muted">Ảnh chụp thực tế, không photoshop</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="glass-icon mx-auto mb-2">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <h6>Thông tin đúng</h6>
                                <small class="text-muted">Mô tả chính xác diện tích, giá cả</small>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-center">
                                <div class="glass-icon mx-auto mb-2">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <h6>Liên hệ hợp lệ</h6>
                                <small class="text-muted">SĐT và email chính xác</small>
                            </div>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Nội dung bị cấm:</h5>
                <div class="alert alert-danger d-flex align-items-start">
                    <i class="fas fa-ban me-3 mt-1"></i>
                    <div>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">• Bài đăng có nội dung khiêu dâm, bạo lực</li>
                            <li class="mb-2">• Thông tin giả mạo, lừa đảo</li>
                            <li class="mb-2">• Đăng tin trùng lặp nhiều lần</li>
                            <li class="mb-2">• Quảng cáo dịch vụ không liên quan</li>
                            <li>• Vi phạm bản quyền hình ảnh</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Section 6: Payment and Fees -->
            <section id="section-6" class="glass-panel mb-4">
                <h2 class="h4 mb-3">
                    <span class="badge bg-primary me-2">6</span>
                    Thanh toán và phí dịch vụ
                </h2>
                
                <h5 class="mb-3">Các gói dịch vụ:</h5>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="glass-container p-3 text-center">
                            <div class="glass-icon mx-auto mb-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <h6>Miễn phí</h6>
                            <p class="text-muted small mb-2">Dành cho người tìm trọ</p>
                            <ul class="list-unstyled small">
                                <li>✓ Tìm kiếm không giới hạn</li>
                                <li>✓ Lưu tin yêu thích</li>
                                <li>✓ Liên hệ trực tiếp</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="glass-container p-3 text-center">
                            <div class="glass-icon mx-auto mb-3">
                                <i class="fas fa-store"></i>
                            </div>
                            <h6>Cơ bản</h6>
                            <p class="text-muted small mb-2">50,000đ/tháng</p>
                            <ul class="list-unstyled small">
                                <li>✓ Đăng 5 tin/tháng</li>
                                <li>✓ Hiển thị thông tin liên hệ</li>
                                <li>✓ Thống kê cơ bản</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="glass-container p-3 text-center">
                            <div class="glass-icon mx-auto mb-3">
                                <i class="fas fa-crown"></i>
                            </div>
                            <h6>VIP</h6>
                            <p class="text-muted small mb-2">150.000 ₫/tháng</p>
                            <ul class="list-unstyled small">
                                <li>✓ Đăng tin không giới hạn</li>
                                <li>✓ Ưu tiên hiển thị</li>
                                <li>✓ Thống kê chi tiết</li>
                                <li>✓ Hỗ trợ ưu tiên</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info d-flex align-items-start">
                    <i class="fas fa-credit-card me-3 mt-1"></i>
                    <div>
                        <strong>Phương thức thanh toán:</strong> Chúng tôi hỗ trợ thanh toán qua 
                        chuyển khoản ngân hàng, ví điện tử (MoMo, ZaloPay) và thẻ tín dụng.
                    </div>
                </div>
            </section>

            <!-- Section 7: Privacy and Data Protection -->
            <section id="section-7" class="glass-panel mb-4">
                <h2 class="h4 mb-3">
                    <span class="badge bg-primary me-2">7</span>
                    Bảo mật thông tin cá nhân
                </h2>
                
                <p class="mb-3">
                    Chúng tôi cam kết bảo vệ thông tin cá nhân của bạn theo 
                    <strong>Luật An toàn thông tin mạng 2015</strong> và 
                    <strong>Nghị định 13/2023/NĐ-CP</strong> về bảo vệ dữ liệu cá nhân.
                </p>
                
                <h5 class="mb-3">Thông tin chúng tôi thu thập:</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-user text-primary me-3 mt-1"></i>
                                Thông tin cá nhân: Họ tên, email, SĐT
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-map-marker-alt text-primary me-3 mt-1"></i>
                                Thông tin địa chỉ cho mục đích giao dịch
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-chart-line text-primary me-3 mt-1"></i>
                                Dữ liệu hành vi sử dụng website
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled">
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-credit-card text-primary me-3 mt-1"></i>
                                Thông tin thanh toán (được mã hóa)
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-camera text-primary me-3 mt-1"></i>
                                Hình ảnh bài đăng và tài liệu xác thực
                            </li>
                            <li class="d-flex align-items-start mb-2">
                                <i class="fas fa-comments text-primary me-3 mt-1"></i>
                                Lịch sử chat và tương tác
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="glass-container p-3">
                    <h6 class="text-success mb-2">
                        <i class="fas fa-shield-alt me-2"></i>
                        Cam kết bảo mật
                    </h6>
                    <ul class="list-unstyled mb-0 small">
                        <li>• Không bán, cho thuê thông tin cá nhân cho bên thứ 3</li>
                        <li>• Sử dụng công nghệ mã hóa SSL 256-bit</li>
                        <li>• Lưu trữ dữ liệu tại datacenter chuẩn ISO 27001</li>
                        <li>• Thường xuyên kiểm tra và cập nhật bảo mật</li>
                    </ul>
                </div>
            </section>

            <!-- Section 8: Legal Responsibilities -->
            <section id="section-8" class="glass-panel mb-4">
                <h2 class="h4 mb-3">
                    <span class="badge bg-primary me-2">8</span>
                    Trách nhiệm pháp lý và giải quyết tranh chấp
                </h2>
                
                <h5 class="mb-3">Trách nhiệm của <?= e($siteSettings['ten_website']) ?>:</h5>
                <ul class="list-unstyled mb-4">
                    <li class="d-flex align-items-start mb-2">
                        <i class="fas fa-check text-success me-3 mt-1"></i>
                        Cung cấp nền tảng ổn định, bảo mật
                    </li>
                    <li class="d-flex align-items-start mb-2">
                        <i class="fas fa-check text-success me-3 mt-1"></i>
                        Kiểm duyệt nội dung theo quy định
                    </li>
                    <li class="d-flex align-items-start mb-2">
                        <i class="fas fa-check text-success me-3 mt-1"></i>
                        Hỗ trợ kỹ thuật và giải đáp thắc mắc
                    </li>
                    <li class="d-flex align-items-start mb-2">
                        <i class="fas fa-times text-danger me-3 mt-1"></i>
                        Không chịu trách nhiệm về tranh chấp giữa người dùng
                    </li>
                    <li class="d-flex align-items-start mb-2">
                        <i class="fas fa-times text-danger me-3 mt-1"></i>
                        Không đảm bảo tính chính xác tuyệt đối của thông tin do người dùng cung cấp
                    </li>
                </ul>
                
                <h5 class="mb-3">Giải quyết tranh chấp:</h5>
                <div class="glass-container p-3">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="glass-icon-sm mx-auto mb-2">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <h6 class="small">Bước 1</h6>
                            <small class="text-muted">Thương lượng trực tiếp</small>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="glass-icon-sm mx-auto mb-2">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h6 class="small">Bước 2</h6>
                            <small class="text-muted">Hòa giải qua Tro365</small>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="glass-icon-sm mx-auto mb-2">
                                <i class="fas fa-gavel"></i>
                            </div>
                            <h6 class="small">Bước 3</h6>
                            <small class="text-muted">Tòa án TP.HCM</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 9: Terms Modification -->
            <section id="section-9" class="glass-panel mb-4">
                <h2 class="h4 mb-3">
                    <span class="badge bg-primary me-2">9</span>
                    Sửa đổi điều khoản
                </h2>
                
                <p class="mb-3">
                    <?= e($siteSettings['ten_website']) ?> có quyền sửa đổi, bổ sung các điều khoản này bất cứ lúc nào. 
                    Các thay đổi sẽ có hiệu lực ngay khi được đăng tải trên website.
                </p>
                
                <div class="alert alert-warning d-flex align-items-start">
                    <i class="fas fa-bell me-3 mt-1"></i>
                    <div>
                        <strong>Thông báo thay đổi:</strong> Chúng tôi sẽ thông báo qua email 
                        và notification trên website ít nhất 7 ngày trước khi áp dụng 
                        các thay đổi quan trọng.
                    </div>
                </div>
                
                <p class="mb-0">
                    Việc bạn tiếp tục sử dụng dịch vụ sau khi các điều khoản được sửa đổi 
                    có nghĩa là bạn đồng ý với những thay đổi đó.
                </p>
            </section>

            <!-- Section 10: Contact Support -->
            <section id="section-10" class="glass-panel mb-4">
                <h2 class="h4 mb-3">
                    <span class="badge bg-primary me-2">10</span>
                    Thông tin liên hệ và hỗ trợ
                </h2>
                
                <p class="mb-4">
                    Nếu bạn có bất kỳ thắc mắc nào về điều khoản sử dụng này, 
                    vui lòng liên hệ với chúng tôi qua các kênh sau:
                </p>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="glass-container p-3">
                            <div class="d-flex align-items-center">
                                <div class="glass-icon-sm me-3">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Email hỗ trợ</h6>
                                    <a href="mailto:<?= e($siteSettings['email_lien_he']) ?>" class="text-primary small"><?= e($siteSettings['email_lien_he']) ?></a>
                                    <small class="d-block text-muted">Phản hồi trong 24h</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-container p-3">
                            <div class="d-flex align-items-center">
                                <div class="glass-icon-sm me-3">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Hotline</h6>
                                    <a href="tel:<?= e($siteSettings['sdt_hotline']) ?>" class="text-primary small"><?= e($siteSettings['sdt_hotline']) ?></a>
                                    <small class="d-block text-muted">24/7 hỗ trợ</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-container p-3">
                            <div class="d-flex align-items-center">
                                <div class="glass-icon-sm me-3">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Địa chỉ văn phòng</h6>
                                    <small class="text-muted">
                                        <?= e($siteSettings['dia_chi_cong_ty']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="glass-container p-3">
                            <div class="d-flex align-items-center">
                                <div class="glass-icon-sm me-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Giờ làm việc</h6>
                                    <small class="text-muted">
                                        Thứ 2 - Thứ 6: 8:00 - 18:00<br>
                                        Thứ 7 - CN: 9:00 - 17:00
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="/contact" class="btn-glass btn-glass-primary me-3">
                        <i class="fas fa-envelope me-2"></i>
                        Gửi thắc mắc
                    </a>
                    <a href="/contact" class="btn-glass">
                        <i class="fas fa-question-circle me-2"></i>
                        Liên hệ hỗ trợ
                    </a>
                </div>
            </section>

            <!-- Legal Footer -->
            <section class="glass-panel mb-4">
                <div class="text-center">
                    <h5 class="mb-3">Hiệu lực điều khoản</h5>
                    <p class="text-muted mb-3">
                        Điều khoản này có hiệu lực từ ngày <strong><?= date('d/m/Y') ?></strong> và thay thế 
                        cho tất cả các phiên bản trước đó.
                    </p>
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="glass-container p-3">
                                <small class="text-muted">
                                    <strong>Tuyên bố pháp lý:</strong> Điều khoản này được soạn thảo theo 
                                    pháp luật Việt Nam và tuân thủ các quy định của Bộ Thông tin và Truyền thông, 
                                    Luật Thương mại điện tử 2005 và các văn bản pháp luật liên quan.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
        
        <!-- Sidebar Navigation -->
        <div class="terms-sidebar">
            <!-- Mobile Navigation Toggle -->
            <div class="d-md-none mb-3">
                <button class="btn-glass w-100" type="button" data-bs-toggle="collapse" data-bs-target="#termsSidebar" aria-expanded="false" aria-controls="termsSidebar">
                    <i class="fas fa-bars me-2"></i>
                    Điều hướng nhanh
                    <i class="fas fa-chevron-down ms-auto"></i>
                </button>
            </div>
            
            <div class="collapse d-md-block" id="termsSidebar">
                <div class="glass-panel terms-sidebar-sticky">
                    <h5 class="mb-3">
                        <i class="fas fa-bookmark me-2 text-primary"></i>
                        Điều hướng nhanh
                    </h5>
                    <nav class="nav flex-column">
                        <a class="nav-link terms-nav-link" href="#section-1">
                            <i class="fas fa-check-circle me-2"></i>
                            Chấp nhận điều khoản
                        </a>
                        <a class="nav-link terms-nav-link" href="#section-2">
                            <i class="fas fa-info-circle me-2"></i>
                            Giới thiệu dịch vụ
                        </a>
                        <a class="nav-link terms-nav-link" href="#section-3">
                            <i class="fas fa-user-plus me-2"></i>
                            Đăng ký tài khoản
                        </a>
                        <a class="nav-link terms-nav-link" href="#section-4">
                            <i class="fas fa-balance-scale me-2"></i>
                            Quyền và nghĩa vụ
                        </a>
                        <a class="nav-link terms-nav-link" href="#section-5">
                            <i class="fas fa-edit me-2"></i>
                            Quy định đăng tin
                        </a>
                        <a class="nav-link terms-nav-link" href="#section-6">
                            <i class="fas fa-credit-card me-2"></i>
                            Thanh toán & phí
                        </a>
                        <a class="nav-link terms-nav-link" href="#section-7">
                            <i class="fas fa-shield-alt me-2"></i>
                            Bảo mật thông tin
                        </a>
                        <a class="nav-link terms-nav-link" href="#section-8">
                            <i class="fas fa-gavel me-2"></i>
                            Trách nhiệm pháp lý
                        </a>
                        <a class="nav-link terms-nav-link" href="#section-9">
                            <i class="fas fa-sync-alt me-2"></i>
                            Sửa đổi điều khoản
                        </a>
                        <a class="nav-link terms-nav-link" href="#section-10">
                            <i class="fas fa-headset me-2"></i>
                            Liên hệ hỗ trợ
                        </a>
                    </nav>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="/contact" class="btn-glass btn-glass-primary">
                            <i class="fas fa-headset me-2"></i>
                            Hỗ trợ
                        </a>
                        <button class="btn-glass" onclick="shareTerms()">
                            <i class="fas fa-share-alt me-2"></i>
                            Chia sẻ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Support Section -->
<div class="container mb-5">
    <div class="glass-card">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="mb-2">Cần hỗ trợ về điều khoản?</h4>
                <p class="text-muted mb-0">
                    Nếu bạn có thắc mắc về điều khoản sử dụng, đội ngũ hỗ trợ của chúng tôi 
                    luôn sẵn sàng giải đáp 24/7.
                </p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/contact" class="btn-glass btn-glass-primary">
                    <i class="fas fa-envelope me-2"></i>
                    Liên hệ ngay
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for Terms Page -->
<style>
/* ===== TERMS PAGE SPECIFIC STYLES WITH HIGH SPECIFICITY ===== */

.terms-page body,
.terms-page .container,
.terms-page .glass-hero {
    line-height: 1.6;
}

/* Text readability improvements */
.terms-page [data-theme="dark"] .glass-hero-content .lead {
    color: #e2e8f0 !important;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
}

.terms-page [data-theme="light"] .glass-hero-content .lead,
.terms-page:not([data-theme="dark"]) .glass-hero-content .lead {
    color: #2d3748 !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Ensure proper text colors for lists */
.terms-page [data-theme="dark"] .list-unstyled li {
    color: #e2e8f0 !important;
}

.terms-page [data-theme="light"] .list-unstyled li,
.terms-page:not([data-theme="dark"]) .list-unstyled li {
    color: #2d3748 !important;
}

/* Additional dark mode fixes for terms page lists */
.terms-page [data-theme="dark"] ul li,
.terms-page [data-theme="dark"] ol li {
    color: #f7fafc !important;
}

.terms-page [data-theme="dark"] .d-flex.align-items-start {
    color: #e2e8f0 !important;
}

.terms-page [data-theme="dark"] .glass-panel ul li,
.terms-page [data-theme="dark"] .glass-card ul li {
    color: #f7fafc !important;
}

/* Quick navigation list items */
.terms-page [data-theme="dark"] .quick-nav ul li,
.terms-page [data-theme="dark"] .quick-nav ol li {
    color: #e2e8f0 !important;
}

.terms-page [data-theme="dark"] .nav-link {
    color: #cbd5e0 !important;
}

.terms-page [data-theme="dark"] .nav-link:hover {
    color: #90cdf4 !important;
}

/* Enhanced Navigation Links */
.terms-nav-link {
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
    position: relative;
    overflow: hidden;
}

/* Enhanced Sticky Sidebar */
.terms-sidebar {
    /* Make the sidebar container sticky */
    position: sticky;
    top: 6rem; /* Account for fixed navbar + comfortable spacing */
    align-self: flex-start; /* Prevent stretching in grid */
    height: fit-content; /* Allow natural height */
}

.terms-sidebar-sticky {
    /* Remove position sticky from inner element since parent handles it */
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 10;
    
    /* Internal scrolling functionality */
    max-height: calc(100vh - 6rem - 2rem); /* Viewport height minus top offset and padding */
    overflow-y: auto;
    overflow-x: hidden;
    
    /* Smooth scrolling behavior */
    scroll-behavior: smooth;
    
    /* Padding adjustments for scrollable content */
    padding-right: 0.5rem; /* Space for scrollbar */
    
    /* Custom scrollbar styling for glassmorphism theme */
    scrollbar-width: thin;
    scrollbar-color: rgba(var(--primary-rgb), 0.3) transparent;
}

/* Webkit scrollbar styling */
.terms-sidebar-sticky::-webkit-scrollbar {
    width: 6px;
}

.terms-sidebar-sticky::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.terms-sidebar-sticky::-webkit-scrollbar-thumb {
    background: rgba(var(--primary-rgb), 0.4);
    border-radius: 3px;
    transition: background 0.3s ease;
}

.terms-sidebar-sticky::-webkit-scrollbar-thumb:hover {
    background: rgba(var(--primary-rgb), 0.6);
}

/* Dark mode scrollbar adjustments */
[data-theme="dark"] .terms-sidebar-sticky::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

[data-theme="dark"] .terms-sidebar-sticky::-webkit-scrollbar-thumb {
    background: rgba(var(--primary-rgb), 0.5);
}

[data-theme="dark"] .terms-sidebar-sticky::-webkit-scrollbar-thumb:hover {
    background: rgba(var(--primary-rgb), 0.7);
}

/* Light mode scrollbar adjustments */
[data-theme="light"] .terms-sidebar-sticky::-webkit-scrollbar-track,
:root:not([data-theme="dark"]) .terms-sidebar-sticky::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
}

[data-theme="light"] .terms-sidebar-sticky::-webkit-scrollbar-thumb,
:root:not([data-theme="dark"]) .terms-sidebar-sticky::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
}

[data-theme="light"] .terms-sidebar-sticky::-webkit-scrollbar-thumb:hover,
:root:not([data-theme="dark"]) .terms-sidebar-sticky::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.4);
}

/* Add subtle shadow when sticky */
.terms-sidebar-sticky.is-sticky {
    box-shadow: 0 8px 32px rgba(var(--primary-rgb), 0.12), 
                0 4px 16px rgba(0, 0, 0, 0.08);
    border-color: rgba(var(--primary-rgb), 0.2);
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .terms-sidebar {
        position: relative;
        top: auto;
        margin-bottom: 2rem;
    }
    
    /* Disable internal scrolling on mobile */
    .terms-sidebar-sticky {
        max-height: none;
        overflow-y: visible;
        padding-right: 0;
        mask-image: none !important;
        -webkit-mask-image: none !important;
    }
}

/* Enhanced scrolling visual feedback */
.terms-sidebar-sticky.is-scrolling {
    border-color: rgba(var(--primary-rgb), 0.4);
}

.terms-sidebar-sticky.has-scroll {
    position: relative;
}

/* Scroll position indicators */
.terms-sidebar-sticky.scroll-at-top::-webkit-scrollbar-thumb {
    background: rgba(var(--primary-rgb), 0.3);
}

.terms-sidebar-sticky.scroll-at-bottom::-webkit-scrollbar-thumb {
    background: rgba(var(--primary-rgb), 0.3);
}

/* Focus styles for keyboard navigation */
.terms-sidebar-sticky:focus {
    outline: 2px solid rgba(var(--primary-rgb), 0.5);
    outline-offset: 2px;
}

/* Smooth scroll behavior for sidebar content */
.terms-sidebar-sticky * {
    scroll-behavior: smooth;
}

@media (min-width: 768px) and (max-width: 991.98px) {
    .terms-sidebar-sticky {
        top: 5rem;
    }
}



.terms-nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(var(--primary-rgb), 0.1), transparent);
    transition: left 0.6s ease;
}

.terms-nav-link:hover::before {
    left: 100%;
}

.terms-nav-link:hover {
    background: var(--glass-bg);
    border-color: var(--glass-border);
    transform: translateX(8px) scale(1.02);
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.15);
    color: var(--primary-color);
}

.terms-nav-link.active {
    background: rgba(var(--primary-rgb), 0.1);
    border-color: rgba(var(--primary-rgb), 0.3);
    color: var(--primary-color);
    transform: translateX(5px);
    box-shadow: 0 4px 16px rgba(var(--primary-rgb), 0.2);
}

.terms-nav-link.active::after {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 60%;
    background: var(--primary-color);
    border-radius: 0 2px 2px 0;
}

/* Enhanced Section Styling */
.terms-content {
    scroll-margin-top: 2rem;
}

.terms-content section {
    scroll-margin-top: 2rem;
    opacity: 1; /* Always visible by default */
    transform: translateY(0); /* No initial transform */
    animation: fadeInUp 0.6s ease forwards;
}

/* Staggered animation delays - but sections remain visible even without animation */
.terms-content section:nth-child(1) { animation-delay: 0.1s; }
.terms-content section:nth-child(2) { animation-delay: 0.2s; }
.terms-content section:nth-child(3) { animation-delay: 0.3s; }
.terms-content section:nth-child(4) { animation-delay: 0.4s; }
.terms-content section:nth-child(5) { animation-delay: 0.5s; }
.terms-content section:nth-child(6) { animation-delay: 0.6s; }
.terms-content section:nth-child(7) { animation-delay: 0.7s; }
.terms-content section:nth-child(8) { animation-delay: 0.8s; }
.terms-content section:nth-child(9) { animation-delay: 0.9s; }
.terms-content section:nth-child(10) { animation-delay: 1s; }

@keyframes fadeInUp {
    from {
        opacity: 0.7;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Enhanced Section Headers */
.terms-content section h2 {
    position: relative;
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
    color: var(--text-primary);
    font-weight: 700;
}

/* Light Mode Header Fixes */
[data-theme="light"] .terms-content section h2,
:root:not([data-theme="dark"]) .terms-content section h2 {
    color: #1a202c !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* Dark Mode Header Fixes */
[data-theme="dark"] .terms-content section h2 {
    color: #f7fafc !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
}

.terms-content section h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), rgba(var(--primary-rgb), 0.3));
    border-radius: 2px;
}

/* Enhanced Badge Styling */
.badge {
    background: linear-gradient(135deg, var(--primary-color), rgba(var(--primary-rgb), 0.8)) !important;
    border: 1px solid rgba(var(--primary-rgb), 0.3);
    backdrop-filter: blur(10px);
    font-weight: 600;
    padding: 0.6rem 0.8rem;
    font-size: 0.85rem;
    color: white !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.3);
}

/* Light Mode Badge Fixes */
[data-theme="light"] .badge,
:root:not([data-theme="dark"]) .badge {
    background: linear-gradient(135deg, #667eea, #764ba2) !important;
    border: 1px solid rgba(102, 126, 234, 0.4);
    color: white !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
}

/* Dark Mode Badge Fixes */
[data-theme="dark"] .badge {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9)) !important;
    border: 1px solid rgba(102, 126, 234, 0.5);
    color: #ffffff !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
}

/* Enhanced Dark Mode Badge for Primary */
[data-theme="dark"] .badge.bg-primary {
    background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
    border: 1px solid rgba(59, 130, 246, 0.6) !important;
    color: #ffffff !important;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8) !important;
    box-shadow: 0 3px 10px rgba(59, 130, 246, 0.5) !important;
}

/* Enhanced Dark Mode Badge for Success */
[data-theme="dark"] .badge.bg-success {
    background: linear-gradient(135deg, #10b981, #059669) !important;
    border: 1px solid rgba(16, 185, 129, 0.6) !important;
    color: #ffffff !important;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8) !important;
    box-shadow: 0 3px 10px rgba(16, 185, 129, 0.4) !important;
}

/* Floating Back to Top Button */
.back-to-top {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    z-index: 1000;
    width: 50px;
    height: 50px;
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-filter);
    border: 1px solid var(--glass-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    text-decoration: none;
    transition: all 0.3s ease;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
}

.back-to-top.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.back-to-top:hover {
    background: rgba(var(--primary-rgb), 0.2);
    border-color: rgba(var(--primary-rgb), 0.4);
    transform: translateY(-3px) scale(1.1);
    box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.3);
    color: var(--primary-color);
}

/* Enhanced Glass Containers */
.glass-container {
    transition: all 0.3s ease;
}

.glass-container:hover {
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.15);
}

/* Light Mode Glass Enhancements */
[data-theme="light"] .glass-panel,
:root:not([data-theme="dark"]) .glass-panel {
    background: rgba(255, 255, 255, 0.95) !important;
    border: 2px solid rgba(0, 0, 0, 0.1) !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
    color: #1a202c !important;
}

[data-theme="light"] .glass-card,
:root:not([data-theme="dark"]) .glass-card {
    background: rgba(255, 255, 255, 0.9) !important;
    border: 2px solid rgba(0, 0, 0, 0.08) !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
    color: #1a202c !important;
}

[data-theme="light"] .glass-container,
:root:not([data-theme="dark"]) .glass-container {
    background: rgba(255, 255, 255, 0.85) !important;
    border: 1px solid rgba(0, 0, 0, 0.12) !important;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06) !important;
    color: #1a202c !important;
}

/* Light Mode List Text */
[data-theme="light"] ul li,
[data-theme="light"] ol li,
:root:not([data-theme="dark"]) ul li,
:root:not([data-theme="dark"]) ol li {
    color: #2d3748 !important;
}

/* Progress Bar */
.reading-progress {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    z-index: 9999;
    transition: width 0.3s ease;
}

/* Quick Navigation Enhancements */
.quick-nav a {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin: 0.25rem 0;
    display: flex;
    align-items: center;
    background: transparent;
    border: 1px solid transparent;
    position: relative;
    overflow: hidden;
}

.quick-nav a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(var(--primary-rgb), 0.1), transparent);
    transition: left 0.6s ease;
}

.quick-nav a:hover::before {
    left: 100%;
}

.quick-nav a:hover {
    background: var(--glass-bg);
    border-color: var(--glass-border);
    transform: translateX(8px) scale(1.02);
    color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.15);
}

/* Light Mode Quick Nav */
[data-theme="light"] .quick-nav a,
:root:not([data-theme="dark"]) .quick-nav a {
    color: #2d3748;
}

[data-theme="light"] .quick-nav a:hover,
:root:not([data-theme="dark"]) .quick-nav a:hover {
    background: rgba(255, 255, 255, 0.9);
    border-color: rgba(102, 126, 234, 0.3);
    color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

/* Dark Mode Quick Nav */
[data-theme="dark"] .quick-nav a {
    color: #e2e8f0;
}

[data-theme="dark"] .quick-nav a:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Mobile Responsive Enhancements */
@media (max-width: 768px) {
    .terms-sidebar {
        margin-top: 2rem;
        order: 2;
    }
    
    .terms-content {
        order: 1;
    }
    
    .glass-grid-2 {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .glass-hero-content h1 {
        font-size: 2.5rem;
    }
    
    .back-to-top {
        bottom: 1rem;
        right: 1rem;
        width: 45px;
        height: 45px;
    }
    
    .terms-nav-link {
        padding: 0.6rem 0.8rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .glass-hero-content h1 {
        font-size: 2rem;
    }
    
    .glass-hero-content .lead {
        font-size: 1rem;
    }
    
    .terms-content section {
        padding: 1.5rem;
    }
    
    .badge {
        font-size: 0.8rem;
        padding: 0.5rem 0.7rem;
    }
}

/* Dark Mode Enhancements */
[data-theme="dark"] .terms-nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

[data-theme="dark"] .back-to-top {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
}

/* Dark Mode Text and Background Fixes */
[data-theme="dark"] .glass-panel {
    background: rgba(255, 255, 255, 0.08) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: #f7fafc !important;
}

[data-theme="dark"] .glass-card {
    background: rgba(255, 255, 255, 0.06) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    color: #f7fafc !important;
}

[data-theme="dark"] .glass-container {
    background: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #e2e8f0 !important;
}

[data-theme="dark"] .alert {
    background: rgba(255, 255, 255, 0.08) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: #f7fafc !important;
}

[data-theme="dark"] .alert-info {
    background: rgba(59, 130, 246, 0.15) !important;
    border-color: rgba(59, 130, 246, 0.3) !important;
    color: #bfdbfe !important;
}

[data-theme="dark"] .alert-warning {
    background: rgba(245, 158, 11, 0.15) !important;
    border-color: rgba(245, 158, 11, 0.3) !important;
    color: #fde68a !important;
}

[data-theme="dark"] .alert-danger {
    background: rgba(239, 68, 68, 0.15) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
    color: #fecaca !important;
}

[data-theme="dark"] .text-muted {
    color: #a0aec0 !important;
}

[data-theme="dark"] .text-primary {
    color: #90cdf4 !important;
}

[data-theme="dark"] .text-success {
    color: #68d391 !important;
}

[data-theme="dark"] .text-danger {
    color: #fc8181 !important;
}

[data-theme="dark"] .btn-glass {
    background: rgba(255, 255, 255, 0.08) !important;
    border-color: rgba(255, 255, 255, 0.15) !important;
    color: #f7fafc !important;
}

[data-theme="dark"] .btn-glass:hover {
    background: rgba(255, 255, 255, 0.12) !important;
    border-color: rgba(255, 255, 255, 0.25) !important;
    color: #ffffff !important;
}

[data-theme="dark"] .btn-glass-primary {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8)) !important;
    border-color: rgba(102, 126, 234, 0.5) !important;
    color: #ffffff !important;
}

[data-theme="dark"] .btn-glass-primary:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9)) !important;
    border-color: rgba(102, 126, 234, 0.7) !important;
    color: #ffffff !important;
}

[data-theme="dark"] h1, 
[data-theme="dark"] h2, 
[data-theme="dark"] h3, 
[data-theme="dark"] h4, 
[data-theme="dark"] h5, 
[data-theme="dark"] h6 {
    color: #f7fafc !important;
}

[data-theme="dark"] p {
    color: #e2e8f0 !important;
}

[data-theme="dark"] .lead {
    color: #cbd5e0 !important;
}

[data-theme="dark"] ul li,
[data-theme="dark"] ol li {
    color: #e2e8f0 !important;
}

/* More specific ul li fixes for dark mode */
[data-theme="dark"] .glass-panel ul li,
[data-theme="dark"] .glass-card ul li,
[data-theme="dark"] .glass-container ul li {
    color: #f7fafc !important;
}

[data-theme="dark"] .list-unstyled li {
    color: #e2e8f0 !important;
}

[data-theme="dark"] .footer-links li,
[data-theme="dark"] .footer-links li a {
    color: #cbd5e0 !important;
}

/* Ensure all nested list items have proper contrast */
[data-theme="dark"] ul ul li,
[data-theme="dark"] ol ol li,
[data-theme="dark"] ul ol li,
[data-theme="dark"] ol ul li {
    color: #cbd5e0 !important;
}

/* Icon list items in dark mode */
[data-theme="dark"] ul li i,
[data-theme="dark"] ol li i {
    color: #90cdf4 !important;
    margin-right: 0.5rem;
}

[data-theme="dark"] .breadcrumb-item a {
    color: #90cdf4 !important;
}

[data-theme="dark"] .breadcrumb-item.active {
    color: #a0aec0 !important;
}

/* Additional comprehensive list fixes for dark mode */
[data-theme="dark"] .terms-content ul,
[data-theme="dark"] .terms-content ol,
[data-theme="dark"] .terms-content li {
    color: #f7fafc !important;
}

[data-theme="dark"] .sidebar ul,
[data-theme="dark"] .sidebar ol,
[data-theme="dark"] .sidebar li {
    color: #e2e8f0 !important;
}

/* Fix for flex list items */
[data-theme="dark"] .d-flex li,
[data-theme="dark"] li {
    color: #e2e8f0 !important;
}

/* Universal dark mode list fixes - catch all */
[data-theme="dark"] * li {
    color: #e2e8f0 !important;
}

/* Force all list elements in dark mode */
[data-theme="dark"] li:not(.breadcrumb-item):not(.nav-item) {
    color: #f7fafc !important;
}

/* Specific dark mode fixes for navigation lists */
[data-theme="dark"] .nav-link {
    color: #e2e8f0 !important;
}

[data-theme="dark"] .nav-link:hover {
    color: #f1f5f9 !important;
}
[data-theme="dark"] li.d-flex {
    color: #f7fafc !important;
}

/* Fix for any remaining list elements */
[data-theme="dark"] div ul li,
[data-theme="dark"] div ol li,
[data-theme="dark"] section ul li,
[data-theme="dark"] section ol li {
    color: #e2e8f0 !important;
}

/* Override any conflicting styles */
[data-theme="dark"] * ul li,
[data-theme="dark"] * ol li {
    color: #e2e8f0 !important;
}

/* Ensure icons in lists are visible */
[data-theme="dark"] ul li .fas,
[data-theme="dark"] ol li .fas,
[data-theme="dark"] li .fas {
    color: #90cdf4 !important;
}

/* Accessibility Enhancements */
.terms-nav-link:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

.back-to-top:focus {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

/* Smooth transitions for reduced motion */
@media (prefers-reduced-motion: reduce) {
    .terms-nav-link,
    .glass-container,
    .back-to-top,
    .terms-content section {
        transition: none;
        animation: none;
    }
    
    /* Ensure sections are always visible when animations are disabled */
    .terms-content section {
        opacity: 1 !important;
        transform: translateY(0) !important;
        visibility: visible !important;
    }
}
</style>

<!-- JavaScript for Terms Page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add terms-page class for CSS targeting
    document.body.classList.add('terms-page');
    
    // Initialize interactive features
    initSmoothScrolling();
    initReadingProgress();
    initBackToTop();
    initSectionHighlighting();
    initLazyAnimations();
    initMobileNavigation();
    
    // Initialize common Tro365 features if available
    if (window.Tro365Common) {
        if (typeof window.Tro365Common.initTooltips === 'function') {
            window.Tro365Common.initTooltips();
        }
        if (typeof window.Tro365Common.initThemeToggle === 'function') {
            window.Tro365Common.initThemeToggle();
        }
    }
    
    // Initialize sidebar scrolling enhancements
    initSidebarScrolling();
});

// Smooth scrolling for navigation links
function initSmoothScrolling() {
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
                const headerOffset = 80; // Account for fixed header
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                
                // Temporarily disable intersection observer
                if (window.sectionObserver) {
                    window.sectionObserver.disconnect();
                }
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                // Update active nav link immediately
                updateActiveNavLink(this.getAttribute('href'));
                
                // Re-enable intersection observer after scroll completes
                setTimeout(() => {
                    if (window.sectionObserver && typeof window.reinitSectionObserver === 'function') {
                        window.reinitSectionObserver();
                    }
                }, 1000);
                
                // Add visual feedback
                target.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    target.style.transform = 'scale(1)';
                }, 200);
                }
            } catch (error) {
                if (window.TRO365_DEBUG) {
                    console.warn('Invalid selector for smooth scrolling:', href, error);
                }
            }
        });
    });
}

// Reading progress bar
function initReadingProgress() {
    // Create progress bar element
    const progressBar = document.createElement('div');
    progressBar.className = 'reading-progress';
    document.body.appendChild(progressBar);
    
    // Update progress on scroll
    function updateProgress() {
        const scrollTop = window.pageYOffset;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        progressBar.style.width = Math.min(scrollPercent, 100) + '%';
    }
    
    window.addEventListener('scroll', updateProgress);
    updateProgress(); // Initial call
}

// Back to top button
function initBackToTop() {
    // Create back to top button
    const backToTop = document.createElement('a');
    backToTop.href = '#';
    backToTop.className = 'back-to-top';
    backToTop.innerHTML = '<i class="fas fa-chevron-up"></i>';
    backToTop.setAttribute('aria-label', 'Về đầu trang');
    document.body.appendChild(backToTop);
    
    // Show/hide based on scroll position
    function toggleBackToTop() {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    }
    
    // Smooth scroll to top
    backToTop.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    window.addEventListener('scroll', toggleBackToTop);
    toggleBackToTop(); // Initial call
}

// Section highlighting and navigation
function initSectionHighlighting() {
    const sections = document.querySelectorAll('.terms-content section');
    const navLinks = document.querySelectorAll('.terms-nav-link');
    
    // Update active navigation on scroll
    function updateActiveNavLink(sectionId) {
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === sectionId) {
                link.classList.add('active');
                
                // Animate the link
                link.style.transform = 'translateX(8px) scale(1.05)';
                setTimeout(() => {
                    link.style.transform = '';
                }, 300);
            }
        });
    }
    
    // Intersection Observer for automatic section highlighting
    const observerOptions = {
        threshold: 0.5,
        rootMargin: '-80px 0px -60% 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        // Only update if we're not manually scrolling
        if (window.isManualScroll) return;
        
        let mostVisible = null;
        let maxRatio = 0;
        
        entries.forEach(entry => {
            if (entry.isIntersecting && entry.intersectionRatio > maxRatio) {
                maxRatio = entry.intersectionRatio;
                mostVisible = entry.target;
            }
        });
        
        if (mostVisible) {
            updateActiveNavLink('#' + mostVisible.id);
            
            // Add entrance animation to section
            mostVisible.style.transform = 'translateY(0)';
            mostVisible.style.opacity = '1';
        }
    }, observerOptions);
    
    sections.forEach(section => {
        observer.observe(section);
    });
    
    // Function to reinitialize observer
    function reinitSectionObserver() {
        sections.forEach(section => {
            observer.observe(section);
        });
        window.isManualScroll = false;
    }
    
    // Make functions globally accessible
    window.updateActiveNavLink = updateActiveNavLink;
    window.sectionObserver = observer;
    window.reinitSectionObserver = reinitSectionObserver;
    window.isManualScroll = false;
}

// Mobile navigation handling
function initMobileNavigation() {
    const mobileToggle = document.querySelector('[data-bs-toggle="collapse"]');
    const sidebar = document.querySelector('#termsSidebar');
    const navLinks = document.querySelectorAll('.terms-nav-link');
    
    if (!mobileToggle || !sidebar) return;
    
    // Auto-close sidebar when clicking nav links on mobile
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Set manual scroll flag
            window.isManualScroll = true;
            
            if (window.innerWidth < 768 && sidebar.classList.contains('show')) {
                const bsCollapse = new bootstrap.Collapse(sidebar, {
                    toggle: false
                });
                bsCollapse.hide();
            }
        });
    });
    
    // Update toggle button icon
    sidebar.addEventListener('show.bs.collapse', function() {
        const icon = mobileToggle.querySelector('.fa-chevron-down');
        if (icon) {
            icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
        }
    });
    
    sidebar.addEventListener('hide.bs.collapse', function() {
        const icon = mobileToggle.querySelector('.fa-chevron-up');
        if (icon) {
            icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
        }
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768 && 
            !sidebar.contains(e.target) && 
            !mobileToggle.contains(e.target) && 
            sidebar.classList.contains('show')) {
            const bsCollapse = new bootstrap.Collapse(sidebar, {
                toggle: false
            });
            bsCollapse.hide();
        }
    });
}

// Lazy animations for better performance
function initLazyAnimations() {
    // Only apply lazy loading to non-section elements to avoid conflicts with fadeInUp animations
    const animatedElements = document.querySelectorAll('.glass-container:not(.terms-content section), .glass-panel:not(.terms-content section)');
    
    const animationObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
                entry.target.classList.add('animate-in');
                animationObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '50px'
    });
    
    animatedElements.forEach(element => {
        // Only pause animations for non-section elements
        if (!element.closest('.terms-content')) {
            element.style.animationPlayState = 'paused';
            animationObserver.observe(element);
        }
    });
    
    // Ensure all terms sections are visible and animated
    const termsSections = document.querySelectorAll('.terms-content section');
    termsSections.forEach((section, index) => {
        // Ensure sections are always visible
        section.style.opacity = '1';
        section.style.transform = 'translateY(0)';
        
        // Add subtle animation if supported
        if (window.CSS && CSS.supports('animation', 'fadeInUp 0.6s ease')) {
            section.style.animationPlayState = 'running';
        }
    });
    
    // Fallback: Ensure all sections are visible after 2 seconds
    setTimeout(() => {
        termsSections.forEach(section => {
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
            section.style.visibility = 'visible';
        });
    }, 2000);
}

// Enhanced sticky sidebar functionality
function initEnhancedStickySidebar() {
    const sidebar = document.querySelector('.terms-sidebar-sticky');
    if (!sidebar) return;
    
    // Create intersection observer to detect when sidebar becomes sticky
    const stickyObserver = new IntersectionObserver(
        ([entry]) => {
            // When the sidebar is not intersecting with its container, it's sticky
            if (!entry.isIntersecting) {
                sidebar.classList.add('is-sticky');
            } else {
                sidebar.classList.remove('is-sticky');
            }
        },
        {
            root: null,
            rootMargin: '-96px 0px 0px 0px', // Account for navbar height + top offset
            threshold: 0
        }
    );
    
    // Observe the sidebar
    stickyObserver.observe(sidebar);
    
    // Smooth scroll behavior when sidebar links are clicked
    const sidebarLinks = sidebar.querySelectorAll('a[href^="#"]');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                // Set manual scroll flag
                window.isManualScroll = true;
                
                // Calculate offset accounting for navbar and some padding
                const navbarHeight = document.querySelector('.tro365-navbar')?.offsetHeight || 60;
                const offset = navbarHeight + 20;
                
                // Temporarily disable intersection observer
                if (window.sectionObserver) {
                    window.sectionObserver.disconnect();
                }
                
                // Smooth scroll to target
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                // Update active nav link immediately
                if (typeof window.updateActiveNavLink === 'function') {
                    window.updateActiveNavLink(this.getAttribute('href'));
                }
                
                // Re-enable intersection observer after scroll completes
                setTimeout(() => {
                    if (window.sectionObserver && typeof window.reinitSectionObserver === 'function') {
                        window.reinitSectionObserver();
                    }
                }, 1000);
                
                // Visual feedback
                target.style.boxShadow = '0 0 0 3px rgba(var(--primary-rgb), 0.3)';
                setTimeout(() => {
                    target.style.boxShadow = '';
                }, 1000);
            }
        });
    });
    
    // Auto-hide sidebar on mobile when scrolling down
    let lastScrollY = window.scrollY;
    let ticking = false;
    
    function updateSidebarOnScroll() {
        const currentScrollY = window.scrollY;
        
        // Only apply on mobile
        if (window.innerWidth < 768) {
            const sidebarCollapse = document.querySelector('#termsSidebar');
            if (sidebarCollapse && sidebarCollapse.classList.contains('show')) {
                // Hide sidebar when scrolling down on mobile
                if (currentScrollY > lastScrollY && currentScrollY > 100) {
                    const bsCollapse = new bootstrap.Collapse(sidebarCollapse, {
                        toggle: false
                    });
                    bsCollapse.hide();
                }
            }
        }
        
        lastScrollY = currentScrollY;
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateSidebarOnScroll);
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', requestTick, { passive: true });
}

// Quick navigation functionality
function scrollToSection(sectionId) {
    const target = document.querySelector(sectionId);
    if (target) {
        // Set manual scroll flag
        window.isManualScroll = true;
        
        const headerOffset = 80;
        const elementPosition = target.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
        
        // Temporarily disable intersection observer
        if (window.sectionObserver) {
            window.sectionObserver.disconnect();
        }
        
        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
        
        // Update active nav link immediately
        if (typeof window.updateActiveNavLink === 'function') {
            window.updateActiveNavLink(sectionId);
        }
        
        // Re-enable intersection observer after scroll completes
        setTimeout(() => {
            if (window.sectionObserver && typeof window.reinitSectionObserver === 'function') {
                window.reinitSectionObserver();
            }
        }, 1000);
        
        // Visual feedback
        target.style.boxShadow = '0 0 0 3px rgba(var(--primary-rgb), 0.3)';
        setTimeout(() => {
            target.style.boxShadow = '';
        }, 1000);
    }
}

// Share functionality
function shareTerms() {
    if (navigator.share) {
        navigator.share({
            title: 'Điều Khoản Sử Dụng - Tro365',
            text: 'Điều khoản và điều kiện sử dụng dịch vụ của Tro365',
            url: window.location.href
        }).catch(console.error);
    } else {
        // Fallback to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            if (window.showToast) {
                showToast('Đã sao chép link điều khoản!', 'success');
            } else {
                alert('Đã sao chép link điều khoản!');
            }
        }).catch(() => {
            if (window.showToast) {
                showToast('Không thể sao chép link', 'error');
            }
        });
    }
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'Home':
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                break;
            case 'End':
                e.preventDefault();
                window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
                break;
        }
    }
});

// Add global functions to window for external access
window.TermsPage = {
    scrollToSection,
    shareTerms,
    updateActiveNavLink: window.updateActiveNavLink
};

// Enhanced sidebar scrolling functionality
function initSidebarScrolling() {
    const sidebar = document.querySelector('.terms-sidebar-sticky');
    if (!sidebar) return;
    
    let scrollTimeout;
    
    // Add scroll event listener for visual feedback
    sidebar.addEventListener('scroll', function() {
        // Add scrolling class for visual feedback
        this.classList.add('is-scrolling');
        
        // Clear existing timeout
        clearTimeout(scrollTimeout);
        
        // Remove scrolling class after scroll ends
        scrollTimeout = setTimeout(() => {
            this.classList.remove('is-scrolling');
        }, 150);
        
        // Update scrollbar visibility based on scroll position
        const isAtTop = this.scrollTop === 0;
        const isAtBottom = this.scrollTop + this.clientHeight >= this.scrollHeight;
        
        this.classList.toggle('scroll-at-top', isAtTop);
        this.classList.toggle('scroll-at-bottom', isAtBottom);
    });
    
    // Initialize scroll position classes
    const isAtTop = sidebar.scrollTop === 0;
    const isAtBottom = sidebar.scrollTop + sidebar.clientHeight >= sidebar.scrollHeight;
    sidebar.classList.toggle('scroll-at-top', isAtTop);
    sidebar.classList.toggle('scroll-at-bottom', isAtBottom);
    
    // Add keyboard navigation for sidebar scrolling
    sidebar.addEventListener('keydown', function(e) {
        switch(e.key) {
            case 'ArrowUp':
                e.preventDefault();
                this.scrollBy({ top: -40, behavior: 'smooth' });
                break;
            case 'ArrowDown':
                e.preventDefault();
                this.scrollBy({ top: 40, behavior: 'smooth' });
                break;
            case 'PageUp':
                e.preventDefault();
                this.scrollBy({ top: -this.clientHeight * 0.8, behavior: 'smooth' });
                break;
            case 'PageDown':
                e.preventDefault();
                this.scrollBy({ top: this.clientHeight * 0.8, behavior: 'smooth' });
                break;
            case 'Home':
                e.preventDefault();
                this.scrollTo({ top: 0, behavior: 'smooth' });
                break;
            case 'End':
                e.preventDefault();
                this.scrollTo({ top: this.scrollHeight, behavior: 'smooth' });
                break;
        }
    });
    
    // Make sidebar focusable for keyboard navigation
    sidebar.setAttribute('tabindex', '0');
    
    // Add visual feedback for scrollable content
    function updateScrollIndicators() {
        const hasScroll = sidebar.scrollHeight > sidebar.clientHeight;
        sidebar.classList.toggle('has-scroll', hasScroll);
        
        if (hasScroll) {
            // Add subtle gradient indicators for scrollable content
            sidebar.style.maskImage = `
                linear-gradient(to bottom, 
                    transparent 0px,
                    black 10px,
                    black calc(100% - 10px),
                    transparent 100%)
            `;
            sidebar.style.webkitMaskImage = sidebar.style.maskImage;
        } else {
            sidebar.style.maskImage = 'none';
            sidebar.style.webkitMaskImage = 'none';
        }
    }
    
    // Check on load and resize
    updateScrollIndicators();
    window.addEventListener('resize', updateScrollIndicators);
    
    // Smooth scroll to active nav item when it's out of view
    function scrollToActiveItem() {
        const activeItem = sidebar.querySelector('.terms-nav-link.active');
        if (activeItem) {
            const sidebarRect = sidebar.getBoundingClientRect();
            const itemRect = activeItem.getBoundingClientRect();
            
            // Check if item is outside visible area
            if (itemRect.top < sidebarRect.top || itemRect.bottom > sidebarRect.bottom) {
                const scrollTop = activeItem.offsetTop - sidebar.offsetTop - (sidebar.clientHeight / 2) + (activeItem.offsetHeight / 2);
                sidebar.scrollTo({
                    top: Math.max(0, scrollTop),
                    behavior: 'smooth'
                });
            }
        }
    }
    
    // Listen for active item changes
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const target = mutation.target;
                if (target.classList.contains('terms-nav-link') && target.classList.contains('active')) {
                    setTimeout(scrollToActiveItem, 100); // Delay to ensure DOM is updated
                }
            }
        });
    });
    
    // Observe all nav links for class changes
    sidebar.querySelectorAll('.terms-nav-link').forEach(link => {
        observer.observe(link, { attributes: true, attributeFilter: ['class'] });
    });
}

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initSmoothScrolling();
    initSectionHighlighting();
    initMobileNavigation();
    initLazyAnimations();
    initEnhancedStickySidebar(); // Enhanced sticky sidebar
    
    // Add resize listener for responsive behavior
    window.addEventListener('resize', function() {
        // Recalculate sticky behavior on resize
        const sidebar = document.querySelector('.terms-sidebar-sticky');
        if (sidebar) {
            // Force recalculation of sticky position
            sidebar.style.position = 'relative';
            setTimeout(() => {
                sidebar.style.position = '';
            }, 10);
        }
    });
    
    // CRITICAL: Ensure all sections are always visible (fallback system)
    const ensureAllSectionsVisible = () => {
        const sections = document.querySelectorAll('.terms-content section');
        sections.forEach((section, index) => {
            // Force visibility regardless of animation state
            section.style.opacity = '1';
            section.style.visibility = 'visible';
            section.style.transform = 'translateY(0)';
            section.style.display = 'block';
        });
        
        // Also ensure glass panels and cards are visible
        const panels = document.querySelectorAll('.glass-panel, .glass-card');
        panels.forEach(panel => {
            if (panel.style.opacity === '0' || panel.style.visibility === 'hidden') {
                panel.style.opacity = '1';
                panel.style.visibility = 'visible';
            }
        });
    };
    
    // Run immediately
    ensureAllSectionsVisible();
    
    // Run after animations should have completed
    setTimeout(ensureAllSectionsVisible, 2000);
});
</script>

<?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>

<?php
// Debug panel removed - using unified footer DebugManager system
?>