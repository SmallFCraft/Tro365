<?php
/**
 * Register Seller Page
 * Tro365 - Website thuê trọ
 */

// Config and autoloader are already loaded by index.php

// Performance optimization includes
require_once __DIR__ . '/../../includes/performance/optimization.php';

use Tro365\Core\Auth;
use Tro365\Services\PerformanceOptimizationService;
use Tro365\Models\User;
use Tro365\Services\Upload;
use Tro365\Core\Database;
use Tro365\Models\Activity;
use Tro365\Services\DataConsistencyService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();
$user = new User();
$db = Database::getInstance();
$dataConsistency = new DataConsistencyService();

$error = '';
$success = '';

// Check if seller registration is enabled
if (!isSellerRegistrationEnabled()) {
    redirect('/');
}

// Check if user is already logged in
$currentUser = $auth->getCurrentUser();
$isLoggedIn = !empty($currentUser);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }

        $userId = null;

        // If user is not logged in, create new user account
        if (!$isLoggedIn) {
            $userData = [
                'HoTen' => cleanInput($_POST['full_name'] ?? ''),
                'TenDN' => cleanInput($_POST['username'] ?? ''),
                'Email' => cleanInput($_POST['email'] ?? ''),
                'MatKhau' => $_POST['password'] ?? '',
                'SDT' => cleanInput($_POST['phone'] ?? ''),
                'DiaChi' => cleanInput($_POST['address'] ?? ''),
                'VaiTroID' => ROLE_USER, // Start as regular user
                'TrangThai' => 1 // Active account
            ];

            // Server-side validation using ValidationHelper (standardized)
            $validation = \Tro365\Helpers\ValidationHelper::enhancedValidate($userData + [
                'password_confirmation' => $_POST['confirm_password'] ?? ''
            ], [
                'HoTen' => 'required|min:3|max:100',
                'TenDN' => 'required|min:3|max:50|regex:/^[a-zA-Z0-9_]+$/',
                'Email' => 'required|email',
                'MatKhau' => 'required|min:8|max:255',
                'password_confirmation' => 'required|same:MatKhau',
                'SDT' => 'regex:' . \Tro365\Helpers\ValidationHelper::getPhonePattern()
            ]);

            if (!$validation['valid']) {
                throw new Exception(implode('. ', array_values($validation['errors'])));
            }

            // Check if username or email already exists (server-side uniqueness)
            if ($user->getByUsername($userData['TenDN'])) {
                throw new Exception('Tên đăng nhập đã tồn tại');
            }
            if ($user->getByEmail($userData['Email'])) {
                throw new Exception('Email đã được sử dụng');
            }

            // Hash password
            $userData['MatKhau'] = password_hash($userData['MatKhau'], PASSWORD_DEFAULT);

            // Create user
            $userId = $user->create($userData);
        } else {
            // User is already logged in
            $userId = $currentUser['ID'];

            // Check if user already has a pending seller registration
            $existingRegistration = $db->selectOne("SELECT ID FROM DangKySeller WHERE KhachHangID = :user_id AND TrangThai = 0", ['user_id' => $userId]);
            if ($existingRegistration) {
                throw new Exception('Bạn đã có đơn đăng ký seller đang chờ duyệt');
            }
        }

        // Create seller registration with data consistency
        $userData = $isLoggedIn ? $currentUser : null;
        $sellerData = $dataConsistency->prepareSellerData($_POST, $userData);
        $sellerData['KhachHangID'] = $userId;

        // Validate seller registration fields with consistency check
        $validationErrors = $dataConsistency->validateSellerData($sellerData, $userData);
        if (!empty($validationErrors)) {
            throw new Exception(implode('. ', $validationErrors));
        }

        // Insert seller registration
        $sellerRegistrationId = $db->insert('DangKySeller', $sellerData);

        // Log activity
        try {
            $activity = new Activity();
            $activity->log(
                $userId,
                'seller_register',
                'Đăng ký trở thành seller: ' . $sellerData['HoTenChuTro'],
                ['seller_registration_id' => $sellerRegistrationId]
            );
        } catch (Exception $e) {
            // Silent fail for activity logging
        }

        // Handle document uploads
        if (!empty($_FILES['documents']['name'][0])) {
            $upload = new Upload();
            $uploadResults = $upload->uploadMultiple($_FILES['documents'], 'documents');

            foreach ($uploadResults as $result) {
                if ($result['success'] && isset($result['file_name'])) {
                    // Update seller registration with document paths based on filename
                    $fileName = $result['file_name'];

                    if (strpos($fileName, 'cccd_truoc') !== false) {
                        $db->update('DangKySeller', ['AnhCCCDTruoc' => $result['web_path']], ['ID' => $sellerRegistrationId]);
                    } elseif (strpos($fileName, 'cccd_sau') !== false) {
                        $db->update('DangKySeller', ['AnhCCCDSau' => $result['web_path']], ['ID' => $sellerRegistrationId]);
                    } elseif (strpos($fileName, 'giay_phep') !== false) {
                        $db->update('DangKySeller', ['GiayPhepKD' => $result['web_path']], ['ID' => $sellerRegistrationId]);
                    }

                    writeLog("Document uploaded for seller registration: " . $result['web_path'] . " (filename: " . $fileName . ")");
                } elseif (!$result['success']) {
                    writeLog("Document upload failed: " . ($result['error'] ?? 'Unknown error'));
                }
            }
        }

        $success = 'Đăng ký seller thành công! Đơn đăng ký của bạn đang chờ admin duyệt.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi" data-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <title>Đăng ký Seller - <?= getWebsiteName() ?></title>
    <meta name="description" content="Trở thành seller tại <?= getWebsiteName() ?>. Đăng tin cho thuê phòng trọ miễn phí và kiếm tiền từ bất động sản của bạn.">

    <!-- Preload critical CSS -->
    <link rel="preload" href="/assets/css/client/main.css" as="style">
    <link rel="preload" href="/assets/css/client/glass-morphism.css" as="style">
    <link rel="preload" href="/assets/css/client/seller-registration.css" as="style">

    <!-- CSS Files -->
    <link href="/assets/css/client/main.css" rel="stylesheet">
    <link href="/assets/css/client/glass-morphism.css" rel="stylesheet">
    <link href="/assets/css/client/seller-registration.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">

    <!-- Theme Color -->
    <meta name="theme-color" content="#667eea" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1a1d29" media="(prefers-color-scheme: dark)">
</head>
<body>
    <?php include __DIR__ . '/../../includes/layouts/client/header.php'; ?>

    <!-- Breadcrumb -->
    <div class="container my-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb glass-breadcrumb">
                <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                <li class="breadcrumb-item active">Đăng ký Seller</li>
            </ol>
        </nav>
    </div>

    <!-- Enhanced Hero Section -->
    <section class="seller-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="seller-hero-content">
                        <h1 class="seller-hero-title">
                            <i class="fas fa-store"></i>
                            Trở thành Seller
                        </h1>
                        <p class="seller-hero-subtitle">
                            Tham gia cộng đồng cho thuê trọ lớn nhất Việt Nam.
                            Đăng tin miễn phí và kiếm tiền từ bất động sản của bạn.
                        </p>
                        <ul class="seller-hero-features">
                            <li class="seller-hero-feature">
                                <i class="fas fa-check-circle"></i>
                                <span>Miễn phí đăng ký và sử dụng</span>
                            </li>
                            <li class="seller-hero-feature">
                                <i class="fas fa-headset"></i>
                                <span>Hỗ trợ khách hàng 24/7</span>
                            </li>
                            <li class="seller-hero-feature">
                                <i class="fas fa-chart-line"></i>
                                <span>Hoa hồng cạnh tranh 5%</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="seller-hero-illustration">
                        <i class="fas fa-handshake fa-10x"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <div class="row">
            <!-- Registration Form -->
            <div class="col-lg-8">
                <div class="seller-form-container">
                    <div class="seller-form-header">
                        <h2 class="seller-form-title">
                            <i class="fas fa-user-plus"></i>
                            Đăng ký tài khoản Seller
                        </h2>

                        <!-- Progress Indicator -->
                        <div class="seller-progress">
                            <div class="seller-progress-line" style="width: 33%;"></div>
                            <div class="seller-progress-step active">1</div>
                            <div class="seller-progress-step">2</div>
                            <div class="seller-progress-step">3</div>
                        </div>
                    </div>
                    <div class="p-4">
                        <?php if ($error): ?>
                            <div class="glass-alert glass-alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <div>
                                    <strong>Lỗi!</strong>
                                    <p><?= e($error) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="glass-alert glass-alert-success">
                                <i class="fas fa-check-circle"></i>
                                <div>
                                    <strong>Thành công!</strong>
                                    <p><?= e($success) ?></p>
                                    <div class="mt-3">
                                        <?php if (isLoggedIn()): ?>
                                            <a href="/" class="btn-seller btn-seller-success">
                                                <i class="fas fa-home"></i>
                                                Về trang chủ
                                            </a>
                                        <?php else: ?>
                                            <a href="/login" class="btn-seller btn-seller-success">
                                                <i class="fas fa-sign-in-alt"></i>
                                                Đăng nhập ngay
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                                <?php if ($isLoggedIn): ?>
                                    <!-- User is logged in - show current info -->
                                    <div class="glass-alert glass-alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <div>
                                            <strong>Đã đăng nhập</strong>
                                            <p>Bạn đang đăng nhập với tài khoản: <strong><?= e($currentUser['HoTen']) ?></strong> (<?= e($currentUser['Email']) ?>)</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Personal Information -->
                                    <div class="seller-form-section">
                                        <h3 class="seller-section-title">
                                            <i class="fas fa-user"></i>
                                            Thông tin cá nhân
                                        </h3>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="seller-floating-label">
                                                    <input type="text"
                                                           class="seller-form-control"
                                                           id="full_name"
                                                           name="full_name"
                                                           value="<?= e($_POST['full_name'] ?? '') ?>"
                                                           placeholder=" "
                                                           required>
                                                    <label for="full_name">
                                                        Họ và tên <span class="text-danger">*</span>
                                                    </label>
                                                </div>
                                                <div id="fullNameFeedback" class="seller-form-feedback"></div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <div class="seller-floating-label">
                                                    <input type="tel"
                                                           class="seller-form-control"
                                                           id="phone"
                                                           name="phone"
                                                           value="<?= e($_POST['phone'] ?? '') ?>"
                                                           placeholder=" ">
                                                    <label for="phone">Số điện thoại</label>
                                                </div>
                                                <div id="phoneFeedback" class="seller-form-feedback"></div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="seller-floating-label">
                                                <textarea class="seller-form-control"
                                                          id="address"
                                                          name="address"
                                                          rows="3"
                                                          placeholder=" "><?= e($_POST['address'] ?? '') ?></textarea>
                                                <label for="address">Địa chỉ</label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!$isLoggedIn): ?>
                                    <!-- Account Information - Only show if not logged in -->
                                    <div class="seller-form-section">
                                        <h3 class="seller-section-title">
                                            <i class="fas fa-key"></i>
                                            Thông tin tài khoản
                                        </h3>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="seller-floating-label">
                                                    <input type="text"
                                                           class="seller-form-control"
                                                           id="username"
                                                           name="username"
                                                           value="<?= e($_POST['username'] ?? '') ?>"
                                                           placeholder=" "
                                                           required>
                                                    <label for="username">
                                                        Tên đăng nhập <span class="text-danger">*</span>
                                                    </label>
                                                </div>
                                                <div id="usernameFeedback" class="seller-form-feedback"></div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <div class="seller-floating-label">
                                                    <input type="email"
                                                           class="seller-form-control"
                                                           id="email"
                                                           name="email"
                                                           value="<?= e($_POST['email'] ?? '') ?>"
                                                           placeholder=" "
                                                           required>
                                                    <label for="email">
                                                        Email <span class="text-danger">*</span>
                                                    </label>
                                                </div>
                                                <div id="emailFeedback" class="seller-form-feedback"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="seller-floating-label">
                                                    <input type="password"
                                                           class="seller-form-control"
                                                           id="password"
                                                           name="password"
                                                           placeholder=" "
                                                           required>
                                                    <label for="password">
                                                        Mật khẩu <span class="text-danger">*</span>
                                                    </label>
                                                </div>
                                                <div id="passwordFeedback" class="seller-form-feedback"></div>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <div class="seller-floating-label">
                                                    <input type="password"
                                                           class="seller-form-control"
                                                           id="confirm_password"
                                                           name="confirm_password"
                                                           placeholder=" "
                                                           required>
                                                    <label for="confirm_password">
                                                        Xác nhận mật khẩu <span class="text-danger">*</span>
                                                    </label>
                                                </div>
                                                <div id="confirmPasswordFeedback" class="seller-form-feedback"></div>
                                            </div>
                                        </div>
                                </div>
                                <?php endif; ?>

                                <!-- Seller Information -->
                                <div class="seller-form-section">
                                    <h3 class="seller-section-title">
                                        <i class="fas fa-store"></i>
                                        Thông tin Seller
                                    </h3>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="seller-floating-label">
                                                <input type="text"
                                                       class="seller-form-control"
                                                       id="owner_name"
                                                       name="owner_name"
                                                       value="<?= e($_POST['owner_name'] ?? ($isLoggedIn ? $currentUser['HoTen'] : '')) ?>"
                                                       placeholder=" "
                                                       required>
                                                <label for="owner_name">
                                                    Họ tên chủ trọ <span class="text-danger">*</span>
                                                </label>
                                            </div>
                                            <div id="ownerNameFeedback" class="seller-form-feedback"></div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <div class="seller-floating-label">
                                                <input type="text"
                                                       class="seller-form-control"
                                                       id="cccd"
                                                       name="cccd"
                                                       value="<?= e($_POST['cccd'] ?? '') ?>"
                                                       placeholder=" "
                                                       required>
                                                <label for="cccd">
                                                    Số CCCD/CMND <span class="text-danger">*</span>
                                                </label>
                                            </div>
                                            <div id="cccdFeedback" class="seller-form-feedback"></div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="seller-floating-label">
                                                <input type="tel"
                                                       class="seller-form-control"
                                                       id="contact_phone"
                                                       name="contact_phone"
                                                       value="<?= e($_POST['contact_phone'] ?? ($isLoggedIn && isset($currentUser['SDT']) ? $currentUser['SDT'] : '')) ?>"
                                                       placeholder=" "
                                                       required>
                                                <label for="contact_phone">
                                                    Số điện thoại liên hệ <span class="text-danger">*</span>
                                                </label>
                                            </div>
                                            <div id="contactPhoneFeedback" class="seller-form-feedback"></div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <div class="seller-floating-label">
                                                <input type="email"
                                                       class="seller-form-control"
                                                       id="contact_email"
                                                       name="contact_email"
                                                       value="<?= e($_POST['contact_email'] ?? ($isLoggedIn ? $currentUser['Email'] : '')) ?>"
                                                       placeholder=" ">
                                                <label for="contact_email">Email liên hệ</label>
                                            </div>
                                            <div id="contactEmailFeedback" class="seller-form-feedback"></div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="seller-floating-label">
                                            <textarea class="seller-form-control"
                                                      id="business_address"
                                                      name="business_address"
                                                      rows="3"
                                                      placeholder=" "
                                                      required><?= e($_POST['business_address'] ?? ($isLoggedIn && isset($currentUser['DiaChi']) ? $currentUser['DiaChi'] : '')) ?></textarea>
                                            <label for="business_address">
                                                Địa chỉ kinh doanh <span class="text-danger">*</span>
                                            </label>
                                        </div>
                                        <div id="businessAddressFeedback" class="seller-form-feedback"></div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="seller-floating-label">
                                            <textarea class="seller-form-control"
                                                      id="reason"
                                                      name="reason"
                                                      rows="4"
                                                      placeholder=" "><?= e($_POST['reason'] ?? '') ?></textarea>
                                            <label for="reason">Lý do muốn trở thành seller</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Documents Upload -->
                                <div class="seller-form-section">
                                    <h3 class="seller-section-title">
                                        <i class="fas fa-file-upload"></i>
                                        Giấy tờ tùy thân (Tùy chọn)
                                    </h3>

                                    <div class="seller-upload-area" onclick="document.getElementById('documents').click()">
                                        <div class="seller-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <h4 class="seller-upload-title">Upload CCCD/CMND hoặc Giấy phép kinh doanh</h4>
                                        <p class="seller-upload-subtitle">
                                            Hỗ trợ: JPG, PNG, PDF. Tối đa <?= formatFileSize(UPLOAD_MAX_SIZE) ?> mỗi file.<br>
                                            Kéo thả file vào đây hoặc click để chọn file
                                        </p>
                                    </div>

                                    <input type="file"
                                           id="documents"
                                           name="documents[]"
                                           multiple
                                           accept="image/*,application/pdf"
                                           style="display: none;">

                                    <div id="documentPreview" class="seller-upload-preview"></div>
                                </div>

                                <!-- Terms -->
                                <div class="mb-4">
                                    <div class="glass-checkbox">
                                        <input type="checkbox"
                                               id="agree_terms"
                                               name="agree_terms"
                                               required>
                                        <label for="agree_terms">
                                            <span class="glass-checkbox-mark"></span>
                                            Tôi đồng ý với
                                            <a href="/terms" target="_blank" class="text-primary">Điều khoản sử dụng</a>
                                            và
                                            <a href="/privacy" target="_blank" class="text-primary">Chính sách bảo mật</a>
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                    <a href="/" class="btn-seller">
                                        <i class="fas fa-arrow-left"></i>
                                        Về trang chủ
                                    </a>
                                    <button type="submit" class="btn-seller btn-seller-primary">
                                        <i class="fas fa-user-plus"></i>
                                        Đăng ký Seller
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Benefits Sidebar -->
            <div class="col-lg-4">
                <div class="seller-benefits">
                    <h3 class="seller-benefits-title">
                        <i class="fas fa-star"></i>
                        Lợi ích khi trở thành Seller
                    </h3>

                    <div class="seller-benefit-card">
                        <div class="seller-benefit-header">
                            <i class="seller-benefit-icon fas fa-money-bill-wave text-success"></i>
                            <h4 class="seller-benefit-title">Thu nhập ổn định</h4>
                        </div>
                        <p class="seller-benefit-description">
                            Kiếm tiền từ việc cho thuê phòng trọ với hoa hồng hấp dẫn 5% mỗi giao dịch thành công
                        </p>
                    </div>

                    <div class="seller-benefit-card">
                        <div class="seller-benefit-header">
                            <i class="seller-benefit-icon fas fa-users text-primary"></i>
                            <h4 class="seller-benefit-title">Khách hàng đông đảo</h4>
                        </div>
                        <p class="seller-benefit-description">
                            Tiếp cận hàng ngàn khách hàng tiềm năng mỗi ngày trên toàn quốc
                        </p>
                    </div>

                    <div class="seller-benefit-card">
                        <div class="seller-benefit-header">
                            <i class="seller-benefit-icon fas fa-chart-line text-info"></i>
                            <h4 class="seller-benefit-title">Công cụ quản lý</h4>
                        </div>
                        <p class="seller-benefit-description">
                            Dashboard chuyên nghiệp để quản lý bài đăng, thống kê và theo dõi doanh thu
                        </p>
                    </div>

                    <div class="seller-benefit-card">
                        <div class="seller-benefit-header">
                            <i class="seller-benefit-icon fas fa-headset text-warning"></i>
                            <h4 class="seller-benefit-title">Hỗ trợ 24/7</h4>
                        </div>
                        <p class="seller-benefit-description">
                            Đội ngũ hỗ trợ chuyên nghiệp luôn sẵn sàng giúp đỡ bạn mọi lúc mọi nơi
                        </p>
                    </div>



                    <div class="seller-important-notice">
                        <div class="seller-notice-header">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h4>Quy trình xét duyệt</h4>
                        </div>
                        <div class="seller-notice-content">
                            <div class="seller-notice-step">
                                <span class="seller-notice-number">1</span>
                                <div class="seller-notice-text">
                                    <strong>Gửi đơn đăng ký</strong>
                                    <p>Hoàn thành form và gửi thông tin đăng ký</p>
                                </div>
                            </div>
                            <div class="seller-notice-step">
                                <span class="seller-notice-number">2</span>
                                <div class="seller-notice-text">
                                    <strong>Xem xét hồ sơ</strong>
                                    <p>Admin sẽ xem xét và kiểm tra thông tin trong vòng 24-48h</p>
                                </div>
                            </div>
                            <div class="seller-notice-step">
                                <span class="seller-notice-number">3</span>
                                <div class="seller-notice-text">
                                    <strong>Thông báo kết quả</strong>
                                    <p>Bạn sẽ nhận email thông báo kết quả phê duyệt</p>
                                </div>
                            </div>
                        </div>
                        <div class="seller-notice-footer">
                            <i class="fas fa-lightbulb"></i>
                            <span><strong>Mẹo:</strong> Cung cấp thông tin đầy đủ và chính xác để tăng tỷ lệ được duyệt</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>

    <script src="/assets/js/client/auth.js"></script>
    <script>
        // Enhanced Form Validation and Interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced password validation
            initPasswordValidation();

            // Real-time form validation
            initRealTimeValidation();

            // Enhanced upload functionality
            initEnhancedUpload();

            // Form auto-save
            initAutoSave();
        });

        // Enhanced password validation
        function initPasswordValidation() {
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');

            if (passwordField) {
                passwordField.addEventListener('input', function() {
                    validatePassword(this.value);
                });
            }

            if (confirmPasswordField) {
                confirmPasswordField.addEventListener('input', function() {
                    validatePasswordConfirmation();
                });
            }
        }

        function validatePassword(password) {
            const feedback = document.getElementById('passwordFeedback');
            if (!feedback) return;

            const requirements = [
                { test: password.length >= 6, text: 'Ít nhất 6 ký tự' },
                { test: /[A-Z]/.test(password), text: 'Có chữ hoa' },
                { test: /[0-9]/.test(password), text: 'Có số' },
                { test: /[^A-Za-z0-9]/.test(password), text: 'Có ký tự đặc biệt' }
            ];

            const passed = requirements.filter(req => req.test).length;
            const strength = passed < 2 ? 'weak' : passed < 3 ? 'medium' : 'strong';

            feedback.innerHTML = `
                <i class="fas fa-shield-alt"></i>
                <div>
                    <strong>Độ mạnh mật khẩu: ${strength === 'weak' ? 'Yếu' : strength === 'medium' ? 'Trung bình' : 'Mạnh'}</strong>
                    <div class="mt-1">
                        ${requirements.map(req =>
                            `<small class="${req.test ? 'text-success' : 'text-muted'}">
                                <i class="fas fa-${req.test ? 'check' : 'times'}"></i> ${req.text}
                            </small>`
                        ).join('<br>')}
                    </div>
                </div>
            `;
            feedback.className = `seller-form-feedback show ${strength === 'weak' ? 'invalid' : 'valid'}`;
        }

        function validatePasswordConfirmation() {
            const password = document.getElementById('password')?.value || '';
            const confirmPassword = document.getElementById('confirm_password')?.value || '';
            const feedback = document.getElementById('confirmPasswordFeedback');

            if (!feedback) return;

            if (confirmPassword === '') {
                feedback.classList.remove('show');
                return;
            }

            const isValid = password === confirmPassword;
            feedback.innerHTML = `
                <i class="fas fa-${isValid ? 'check-circle' : 'exclamation-circle'}"></i>
                ${isValid ? 'Mật khẩu khớp' : 'Mật khẩu không khớp'}
            `;
            feedback.className = `seller-form-feedback show ${isValid ? 'valid' : 'invalid'}`;
        }

        // Real-time form validation
        function initRealTimeValidation() {
            // Username validation
            const usernameField = document.getElementById('username');
            if (usernameField) {
                let usernameTimeout;
                usernameField.addEventListener('input', function() {
                    clearTimeout(usernameTimeout);
                    usernameTimeout = setTimeout(() => {
                        validateUsername(this.value);
                    }, 500);
                });
            }

            // Email validation
            const emailField = document.getElementById('email');
            if (emailField) {
                let emailTimeout;
                emailField.addEventListener('input', function() {
                    clearTimeout(emailTimeout);
                    emailTimeout = setTimeout(() => {
                        validateEmail(this.value);
                    }, 500);
                });
            }

            // Phone validation
            const phoneField = document.getElementById('phone');
            if (phoneField) {
                phoneField.addEventListener('input', function() {
                    formatPhoneNumber(this);
                    validatePhone(this.value);
                });
            }
        }

        function validateUsername(username) {
            const feedback = document.getElementById('usernameFeedback');
            if (!feedback) return;

            if (username.length < 3) {
                showFeedback(feedback, 'Tên đăng nhập phải có ít nhất 3 ký tự', false);
                return;
            }

            // Use canonical username pattern from FormValidator
            const isValidPattern = FormValidator.rules.username ? FormValidator.rules.username(username) : /^[a-zA-Z0-9_]+$/.test(username);
            if (!isValidPattern) {
                showFeedback(feedback, 'Chỉ được sử dụng chữ, số và dấu gạch dưới', false);
                return;
            }

            // Real API check for username availability
            showFeedback(feedback, 'Đang kiểm tra...', true, 'info');
            fetch('/api/check-availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ type: 'username', value: username })
            })
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    showFeedback(feedback, data.message || 'Tên đăng nhập có thể sử dụng', true);
                } else {
                    showFeedback(feedback, data.message || 'Tên đăng nhập đã tồn tại', false);
                }
            })
            .catch(() => {
                showFeedback(feedback, 'Không thể kiểm tra tên đăng nhập. Vui lòng thử lại.', false);
            });
        }

        function validateEmail(email) {
            const feedback = document.getElementById('emailFeedback');
            if (!feedback) return;

            // Basic email validation handled by FormValidator
            // Only check availability here

            // Real API check for email availability
            showFeedback(feedback, 'Đang kiểm tra...', true, 'info');
            fetch('/api/check-availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ type: 'email', value: email })
            })
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    showFeedback(feedback, data.message || 'Email có thể sử dụng', true);
                } else {
                    showFeedback(feedback, data.message || 'Email đã được sử dụng', false);
                }
            })
            .catch(() => {
                showFeedback(feedback, 'Không thể kiểm tra email. Vui lòng thử lại.', false);
            });
        }

        function validatePhone(phone) {
            const feedback = document.getElementById('phoneFeedback');
            if (!feedback || !phone) return;

            // Phone validation handled by FormValidator (standardized)
            showFeedback(feedback, 'Số điện thoại hợp lệ', true);
        }

        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 10) value = value.slice(0, 10);

            if (value.length > 6) {
                value = value.replace(/(\d{4})(\d{3})(\d{3})/, '$1 $2 $3');
            } else if (value.length > 3) {
                value = value.replace(/(\d{4})(\d{3})/, '$1 $2');
            }

            input.value = value;
        }

        function showFeedback(element, message, isValid, type = null) {
            const iconClass = type === 'info' ? 'fa-spinner fa-spin' : isValid ? 'fa-check-circle' : 'fa-exclamation-circle';
            element.innerHTML = `<i class="fas ${iconClass}"></i> ${message}`;
            element.className = `seller-form-feedback show ${type || (isValid ? 'valid' : 'invalid')}`;
        }

        // Enhanced upload functionality
        function initEnhancedUpload() {
            const documentsField = document.getElementById('documents');
            const uploadArea = document.querySelector('.seller-upload-area');
            const preview = document.getElementById('documentPreview');

            if (documentsField) {
                documentsField.addEventListener('change', function(e) {
                    handleFileSelection(Array.from(e.target.files));
                });
            }

            if (uploadArea) {
                // Drag and drop functionality
                uploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('dragover');
                });

                uploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                });

                uploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');

                    const files = Array.from(e.dataTransfer.files);
                    handleFileSelection(files);
                });
            }

            function handleFileSelection(files) {
                if (!preview) return;

                preview.innerHTML = '';

                files.forEach((file, index) => {
                    if (validateFile(file)) {
                        createFilePreview(file, index);
                    }
                });
            }

            function validateFile(file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

                if (file.size > maxSize) {
                    alert(`File ${file.name} quá lớn. Kích thước tối đa là 5MB.`);
                    return false;
                }

                if (!allowedTypes.includes(file.type)) {
                    alert(`File ${file.name} không được hỗ trợ. Chỉ chấp nhận JPG, PNG, PDF.`);
                    return false;
                }

                return true;
            }

            function createFilePreview(file, index) {
                const fileItem = document.createElement('div');
                fileItem.className = 'seller-upload-item';

                const iconClass = file.type.includes('pdf') ? 'fa-file-pdf' : 'fa-file-image';
                const iconColor = file.type.includes('pdf') ? 'text-danger' : 'text-success';

                fileItem.innerHTML = `
                    <i class="seller-upload-item-icon fas ${iconClass} ${iconColor}"></i>
                    <div class="seller-upload-item-info">
                        <h6 class="seller-upload-item-name">${file.name}</h6>
                        <p class="seller-upload-item-size">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                    </div>
                    <button type="button" class="seller-upload-item-remove" onclick="removeFile(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;

                preview.appendChild(fileItem);
            }
        }

        function removeFile(index) {
            const preview = document.getElementById('documentPreview');
            const items = preview.querySelectorAll('.seller-upload-item');
            if (items[index]) {
                items[index].remove();
            }
        }

        // Auto-save functionality
        function initAutoSave() {
            const form = document.querySelector('form');
            if (!form) return;

            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('input', debounce(saveFormData, 1000));
            });

            // Load saved data on page load
            loadFormData();
        }

        function saveFormData() {
            const form = document.querySelector('form');
            if (!form) return;

            const formData = new FormData(form);
            const data = {};

            for (let [key, value] of formData.entries()) {
                if (key !== 'csrf_token' && key !== 'password' && key !== 'confirm_password') {
                    data[key] = value;
                }
            }

            localStorage.setItem('seller_registration_draft', JSON.stringify(data));
        }

        function loadFormData() {
            const savedData = localStorage.getItem('seller_registration_draft');
            if (!savedData) return;

            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(key => {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input && input.type !== 'password') {
                        input.value = data[key];
                    }
                });
            } catch (e) {
                console.error('Error loading saved form data:', e);
            }
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Clear saved data on successful submission
        window.addEventListener('beforeunload', function() {
            const form = document.querySelector('form');
            if (form && form.checkValidity()) {
                localStorage.removeItem('seller_registration_draft');
            }
        });
    </script>
</body>
</html>
