<?php
/**
 * Contact Page - Modern Glass Morphism Design
 * Tro365 - Website thuê trọ
 */

// Set page variables for header
$pageTitle = 'Liên hệ';
$pageDescription = 'Liên hệ với Trọ 365 để được hỗ trợ tốt nhất. Chúng tôi luôn sẵn sàng giải đáp mọi thắc mắc của bạn về dịch vụ thuê trọ.';

// Additional CSS for contact page
$additionalCSS = [
    '/assets/css/client/glass-morphism.css',
    '/assets/css/client/contact.css'
];

// Additional JS for contact page
$additionalJS = [
    '/assets/js/client/contact.js'
];

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }
        
        $contactData = [
            'name' => cleanInput($_POST['name'] ?? ''),
            'email' => cleanInput($_POST['email'] ?? ''),
            'phone' => cleanInput($_POST['phone'] ?? ''),
            'subject' => cleanInput($_POST['subject'] ?? ''),
            'message' => cleanInput($_POST['message'] ?? '')
        ];
        
        // Validate required fields
        if (empty($contactData['name']) || empty($contactData['email']) || empty($contactData['message'])) {
            throw new Exception('Vui lòng nhập đầy đủ thông tin bắt buộc');
        }
        
        if (!isValidEmail($contactData['email'])) {
            throw new Exception('Email không hợp lệ');
        }
        
        if (!empty($contactData['phone']) && !isValidPhone($contactData['phone'])) {
            throw new Exception('Số điện thoại không hợp lệ');
        }

        // Send contact email using helper function
        $emailSent = sendContactEmail(
            $contactData['name'],
            $contactData['email'],
            $contactData['subject'] ?: 'Liên hệ từ website',
            $contactData['message']
        );

        if ($emailSent) {
            // Log successful contact form submission
            writeLog("Contact form submitted and email sent", 'info', 'contact', [
                'name' => $contactData['name'],
                'email' => $contactData['email'],
                'subject' => $contactData['subject'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            $success = 'Cảm ơn bạn đã liên hệ! Email của bạn đã được gửi thành công. Chúng tôi sẽ phản hồi trong thời gian sớm nhất.';
        } else {
            // Log failed email sending
            writeLog("Contact form email failed", 'error', 'contact', [
                'contact_data' => $contactData,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            throw new Exception('Có lỗi xảy ra khi gửi email. Vui lòng thử lại sau hoặc liên hệ trực tiếp qua số điện thoại.');
        }

        // Clear form data on success
        $_POST = [];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Include header
include __DIR__ . '/../../includes/layouts/client/header.php';
?>

<!-- Hero Section with Modern Glass Morphism -->
<section class="contact-hero">
    <div class="container">
        <div class="contact-hero-content">
            <h1 class="contact-hero-title">Liên hệ với chúng tôi</h1>
            <p class="contact-hero-subtitle">
                Chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7<br>
                Hãy để lại thông tin, chúng tôi sẽ phản hồi trong thời gian sớm nhất
            </p>
        </div>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
            <li class="breadcrumb-item active">Liên hệ</li>
        </ol>
    </nav>
</div>

<!-- Contact Information Cards -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Thông tin liên hệ</h2>
            <p class="lead text-muted">Nhiều cách để bạn có thể kết nối với chúng tôi</p>
        </div>
        
        <div class="contact-info-grid">
            <!-- Phone Contact -->
            <div class="contact-info-card">
                <div class="contact-info-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <h3 class="contact-info-title">Điện thoại</h3>
                <div class="contact-info-details">
                    <p><strong>Hotline:</strong> <a href="tel:+84901234567">0901 234 567</a></p>
                    <p><strong>Hỗ trợ kỹ thuật:</strong> <a href="tel:+84901234568">0901 234 568</a></p>
                    <p class="text-muted">Thời gian: 24/7</p>
                </div>
            </div>

            <!-- Email Contact -->
            <div class="contact-info-card">
                <div class="contact-info-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3 class="contact-info-title">Email</h3>
                <div class="contact-info-details">
                    <p><strong>Tổng đài:</strong> <a href="mailto:contact@tro365.vn">contact@tro365.vn</a></p>
                    <p><strong>Hỗ trợ:</strong> <a href="mailto:support@tro365.vn">support@tro365.vn</a></p>
                    <p class="text-muted">Phản hồi trong 2-4 giờ</p>
                </div>
            </div>

            <!-- Address Contact -->
            <div class="contact-info-card">
                <div class="contact-info-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3 class="contact-info-title">Địa chỉ</h3>
                <div class="contact-info-details">
                    <p><strong>Văn phòng chính:</strong></p>
                    <p>123 Đường Lê Lợi, Quận 1<br>TP. Hồ Chí Minh, Việt Nam</p>
                    <p class="text-muted">Thứ 2 - Thứ 6: 8:00 - 18:00</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form Section -->
<section class="py-5 contact-form-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="contact-form-container">
                    <h2 class="contact-form-title">Gửi tin nhắn cho chúng tôi</h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= e($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <form id="contactForm" method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="contact-form-group">
                                    <label for="name" class="contact-form-label">Họ và tên *</label>
                                    <input type="text"
                                           id="name"
                                           name="name"
                                           class="contact-form-input"
                                           value="<?= e($_POST['name'] ?? '') ?>"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="contact-form-group">
                                    <label for="email" class="contact-form-label">Email *</label>
                                    <input type="email"
                                           id="email"
                                           name="email"
                                           class="contact-form-input"
                                           value="<?= e($_POST['email'] ?? '') ?>"
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="contact-form-group">
                                    <label for="phone" class="contact-form-label">Số điện thoại</label>
                                    <input type="tel"
                                           id="phone"
                                           name="phone"
                                           class="contact-form-input"
                                           value="<?= e($_POST['phone'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="contact-form-group">
                                    <label for="subject" class="contact-form-label">Chủ đề</label>
                                    <input type="text"
                                           id="subject"
                                           name="subject"
                                           class="contact-form-input"
                                           value="<?= e($_POST['subject'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="contact-form-group">
                            <label for="message" class="contact-form-label">Nội dung tin nhắn *</label>
                            <textarea id="message"
                                      name="message"
                                      class="contact-form-textarea"
                                      required><?= e($_POST['message'] ?? '') ?></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="contact-form-submit">
                                <i class="fas fa-paper-plane me-2"></i>
                                Gửi tin nhắn
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5">
    <div class="container">
        <div class="faq-container">
            <h2 class="faq-title">Câu hỏi thường gặp</h2>

            <div class="faq-item">
                <div class="faq-header">
                    <h3 class="faq-question">Làm thế nào để đăng tin cho thuê trọ?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-body">
                    <p class="faq-answer">
                        Để đăng tin cho thuê trọ, bạn cần đăng ký tài khoản Seller, sau đó liên hệ với admin để được duyệt.
                        Khi đã được duyệt, bạn có thể đăng tin miễn phí và nhận hoa hồng 5% khi có giao dịch thành công.
                    </p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-header">
                    <h3 class="faq-question">Tôi có thể tìm kiếm phòng trọ theo khu vực không?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-body">
                    <p class="faq-answer">
                        Có, bạn có thể tìm kiếm phòng trọ theo tỉnh/thành phố, quận/huyện và phường/xã.
                        Hệ thống cũng hỗ trợ tìm kiếm theo giá, diện tích, số phòng và nhiều tiêu chí khác.
                    </p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-header">
                    <h3 class="faq-question">Chi phí sử dụng dịch vụ như thế nào?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-body">
                    <p class="faq-answer">
                        Tro365 hoàn toàn miễn phí cho người tìm trọ. Đối với chủ nhà/Seller, việc đăng tin cũng miễn phí,
                        chúng tôi chỉ thu hoa hồng 5% khi có giao dịch thành công.
                    </p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-header">
                    <h3 class="faq-question">Làm sao để liên hệ với chủ nhà?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-body">
                    <p class="faq-answer">
                        Bạn có thể liên hệ trực tiếp với chủ nhà thông qua số điện thoại hoặc email được hiển thị trong tin đăng.
                        Hệ thống cũng hỗ trợ chat trực tiếp để trao đổi nhanh chóng.
                    </p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-header">
                    <h3 class="faq-question">Tôi có thể lưu tin yêu thích không?</h3>
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-body">
                    <p class="faq-answer">
                        Có, bạn có thể tạo tài khoản và lưu các tin đăng yêu thích vào danh sách wishlist.
                        Điều này giúp bạn dễ dàng theo dõi và so sánh các lựa chọn.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-5 map-section">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Vị trí văn phòng</h2>
            <p class="lead text-muted">Hãy đến thăm chúng tôi tại văn phòng chính</p>
        </div>

        <div class="map-container">
            <div class="map-placeholder">
                <i class="fas fa-map-marked-alt"></i>
                <h4 class="mt-3 mb-2">Bản đồ tương tác</h4>
                <p class="mb-0">123 Đường Lê Lợi, Quận 1, TP. Hồ Chí Minh</p>
                <small class="text-muted">Bản đồ sẽ được tích hợp trong phiên bản tiếp theo</small>
            </div>
        </div>
    </div>
</section>

<!-- Social Media Links -->
<section class="py-5">
    <div class="container">
        <div class="social-links-container">
            <h2 class="social-links-title">Kết nối với chúng tôi</h2>
            <div class="social-links">
                <a href="https://facebook.com/tro365" class="social-link" target="_blank" rel="noopener">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://zalo.me/tro365" class="social-link" target="_blank" rel="noopener">
                    <i class="fab fa-telegram"></i>
                </a>
                <a href="https://youtube.com/tro365" class="social-link" target="_blank" rel="noopener">
                    <i class="fab fa-youtube"></i>
                </a>
                <a href="https://instagram.com/tro365" class="social-link" target="_blank" rel="noopener">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="mailto:contact@tro365.vn" class="social-link">
                    <i class="fas fa-envelope"></i>
                </a>
                <a href="tel:+84901234567" class="social-link">
                    <i class="fas fa-phone"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>
