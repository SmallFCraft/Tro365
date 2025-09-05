<?php
/**
 * Edit Profile Page - Glass Morphism UI
 * Tro365 - Website thuê trọ
 * Mobile-First Responsive Design with Light/Dark Mode
 */

// Load configuration and dependencies
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/constants.php';

// Performance optimization includes
require_once __DIR__ . '/../../../includes/performance/optimization.php';

// Load helper functions
require_once __DIR__ . '/../../../includes/functions/helpers.php';
require_once __DIR__ . '/../../../includes/functions/auth.php';
require_once __DIR__ . '/../../../includes/functions/validation.php';

use Tro365\Core\Auth;
use Tro365\Models\User;
use Tro365\Services\Upload;
use Tro365\Activity;
use Tro365\Core\Database;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();
$user = new User();
$db = Database::getInstance();

// Require login
if (!$auth->isLoggedIn()) {
    setFlashMessage(MSG_ERROR, 'Vui lòng đăng nhập để chỉnh sửa hồ sơ');
    redirect('/login');
}

// Get current user with fresh data (auto-refreshes every 5 minutes)
$currentUser = $auth->getCurrentUser(true); // Force refresh to get latest email verification status
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }

        $updateData = [
            'HoTen' => cleanInput($_POST['full_name'] ?? ''),
            'NgaySinh' => cleanInput($_POST['birth_date'] ?? '') ?: null,
            'GioiTinh' => cleanInput($_POST['gender'] ?? '') ?: null,
            'CCCD' => cleanInput($_POST['cccd'] ?? '') ?: null,
            'SDT' => cleanInput($_POST['phone'] ?? ''),
            'DiaChi' => cleanInput($_POST['address'] ?? '')
        ];

        // Validate required fields
        if (empty($updateData['HoTen'])) {
            throw new Exception('Họ và tên không được để trống');
        }

        if (!empty($updateData['SDT']) && !isValidPhone($updateData['SDT'])) {
            throw new Exception('Số điện thoại không hợp lệ');
        }

        // Validate birth date
        if (!empty($updateData['NgaySinh'])) {
            $birthDate = \DateTime::createFromFormat('Y-m-d', $updateData['NgaySinh']);
            if (!$birthDate || $birthDate->format('Y-m-d') !== $updateData['NgaySinh']) {
                throw new Exception('Ngày sinh không hợp lệ');
            }

            // Check if birth date is not in the future
            if ($birthDate > new \DateTime()) {
                throw new Exception('Ngày sinh không thể là ngày trong tương lai');
            }

            // Check minimum age (13 years old)
            $minAge = new \DateTime('-13 years');
            if ($birthDate > $minAge) {
                throw new Exception('Bạn phải ít nhất 13 tuổi');
            }
        }

        // Validate CCCD
        if (!empty($updateData['CCCD'])) {
            if (!preg_match('/^[0-9]{9,12}$/', $updateData['CCCD'])) {
                throw new Exception('CCCD phải có từ 9-12 chữ số');
            }
        }

        // Handle avatar upload
        if (!empty($_FILES['avatar']['name'])) {
            $upload = new Upload();
            $uploadResult = $upload->uploadAvatar($_FILES['avatar'], $currentUser['TenDN']);

            if ($uploadResult['success']) {
                $updateData['AnhDaiDien'] = $uploadResult['web_path'];
            } else {
                throw new Exception('Lỗi upload avatar: ' . $uploadResult['error']);
            }
        }

        // Update user
        $user->update($currentUser['ID'], $updateData);

        // Update session
        $auth->updateSession();

        // Log activity
        try {
            $activity = new Activity();
            $activity->logUpdateProfile($currentUser['ID'], array_keys($updateData));
        } catch (Exception $e) {
            // Silent fail for activity logging
            writeLog("Activity log error: " . $e->getMessage());
        }

        $success = 'Cập nhật hồ sơ thành công!';

        // Refresh current user data
        $currentUser = $auth->getCurrentUser();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Set page variables for header
$pageTitle = 'Chỉnh sửa hồ sơ';
$pageDescription = 'Cập nhật thông tin cá nhân với giao diện Glass Morphism hiện đại';

// Additional CSS files for this page
$additionalCSS = [
    '/assets/css/client/glass-morphism.css',
    '/assets/css/client/profile.css'
];

// Include header
include __DIR__ . '/../../../includes/layouts/client/header.php';
?>

    <!-- Profile Hero Section -->
    <section class="profile-hero">
        <div class="profile-hero-content">
            <div class="profile-avatar-container">
                <?= getUserAvatarHtml($currentUser['AnhDaiDien'], 'profile-avatar', 'Avatar') ?>
                <div class="profile-avatar-badge">
                    <?php if (!empty($currentUser['email_verified_at'])): ?>
                        <i class="fas fa-check-circle text-success"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-info">
                <h2><?= e($currentUser['HoTen']) ?></h2>

                <div class="profile-info-items">
                    <div class="profile-info-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= e($currentUser['Email']) ?></span>
                        <?php if (!empty($currentUser['email_verified_at'])): ?>
                            <span class="badge bg-success ms-2">
                                <i class="fas fa-check-circle me-1"></i>
                                Đã xác thực
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning ms-2">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Chưa xác thực
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($currentUser['SDT'])): ?>
                        <div class="profile-info-item">
                            <i class="fas fa-phone"></i>
                            <span><?= e($currentUser['SDT']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($currentUser['NgaySinh'])): ?>
                        <div class="profile-info-item">
                            <i class="fas fa-birthday-cake"></i>
                            <span>Sinh ngày <?= formatDate($currentUser['NgaySinh']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($currentUser['GioiTinh'])): ?>
                        <div class="profile-info-item">
                            <i class="fas fa-venus-mars"></i>
                            <span><?= e($currentUser['GioiTinh']) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="profile-info-item">
                        <i class="fas fa-calendar"></i>
                        <span>Tham gia từ <?= formatDate($currentUser['NgayTao'] ?? date('Y-m-d')) ?></span>
                    </div>
                </div>

                <?php
                $roleClass = '';
                $roleText = '';
                $roleIcon = '';
                switch ($currentUser['VaiTroID']) {
                    case ROLE_ADMIN:
                        $roleClass = 'bg-danger';
                        $roleText = 'Quản trị viên';
                        $roleIcon = 'fas fa-crown';
                        break;
                    case ROLE_MODERATOR:
                        $roleClass = 'bg-warning';
                        $roleText = 'Điều hành viên';
                        $roleIcon = 'fas fa-shield-alt';
                        break;
                    case ROLE_SELLER:
                        $roleClass = 'bg-success';
                        $roleText = 'Seller';
                        $roleIcon = 'fas fa-store';
                        break;
                    default:
                        $roleClass = 'bg-primary';
                        $roleText = 'Thành viên';
                        $roleIcon = 'fas fa-user';
                }
                ?>
                <div class="profile-role-badge">
                    <i class="<?= $roleIcon ?>"></i>
                    <span><?= $roleText ?></span>
                </div>
            </div>
        </div>
    </section>

    <div class="profile-container">
        <!-- Glass Morphism Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <div class="glass-container" style="padding: 1rem 1.5rem; border-radius: 15px;">
                <ol class="breadcrumb mb-0" style="background: transparent;">
                    <li class="breadcrumb-item">
                        <a href="/" class="text-decoration-none">
                            <i class="fas fa-home me-1"></i>
                            Trang chủ
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="/profile" class="text-decoration-none">
                            <i class="fas fa-user me-1"></i>
                            Trang cá nhân
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <i class="fas fa-edit me-1"></i>
                        Chỉnh sửa hồ sơ
                    </li>
                </ol>
            </div>
        </nav>

        <!-- Glass Morphism Layout Grid -->
        <div class="glass-grid-2">
            <!-- Main Edit Form -->
            <div class="glass-panel">
                <div class="settings-card-header">
                    <div class="settings-card-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="settings-card-title">
                        <h5>Chỉnh sửa hồ sơ</h5>
                        <p>Cập nhật thông tin cá nhân của bạn</p>
                    </div>
                </div>

                <!-- Alert Messages with Glass Morphism -->
                <?php if ($error): ?>
                    <div class="glass-container mb-4" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); padding: 1rem 1.5rem;">
                        <div class="d-flex align-items-center">
                            <div class="glass-icon-sm me-3" style="background: rgba(239, 68, 68, 0.2); color: #ef4444;">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong>Lỗi!</strong> <?= e($error) ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="glass-container mb-4" style="background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3); padding: 1rem 1.5rem;">
                        <div class="d-flex align-items-center">
                            <div class="glass-icon-sm me-3" style="background: rgba(34, 197, 94, 0.2); color: #22c55e;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong>Thành công!</strong> <?= e($success) ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="profileEditForm">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <!-- Glass Morphism Avatar Upload Section -->
                    <div class="settings-card mb-4">
                        <div class="settings-card-header">
                            <div class="settings-card-icon" style="background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.2); color: #a855f7;">
                                <i class="fas fa-image"></i>
                            </div>
                            <div class="settings-card-title">
                                <h5>Ảnh đại diện</h5>
                                <p>Tải lên ảnh đại diện mới cho tài khoản</p>
                            </div>
                        </div>

                        <div class="text-center">
                            <div class="avatar-upload-container position-relative d-inline-block mb-3">
                                <img src="<?= getUserAvatar($currentUser['AnhDaiDien']) ?>"
                                     alt="Avatar"
                                     class="profile-avatar"
                                     id="avatarPreview"
                                     style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255, 255, 255, 0.3); transition: all 0.3s ease;">

                                <div class="upload-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                                     style="background: rgba(0,0,0,0.6); border-radius: 50%; opacity: 0; transition: opacity 0.3s ease; cursor: pointer;"
                                     onclick="document.getElementById('avatar').click()">
                                    <i class="fas fa-camera fa-2x text-white"></i>
                                </div>

                                <input type="file"
                                       id="avatar"
                                       name="avatar"
                                       accept="<?= implode(',', array_map(fn($ext) => '.'.$ext, getUploadAllowedExtensionsArray())) ?>"
                                       style="display: none;">
                            </div>

                            <div class="settings-info-box">
                                <i class="fas fa-info-circle"></i>
                                <span>
    Click vào ảnh để thay đổi. Hỗ trợ: <?= strtoupper(implode(', ', getUploadAllowedExtensionsArray())) ?>.
    Tối đa <?= formatFileSize(getUploadMaxSizeBytes()) ?>.
</span>
                            </div>
                        </div>
                    </div>

                    <!-- Glass Morphism Personal Information Section -->
                    <div class="settings-card mb-4">
                        <div class="settings-card-header">
                            <div class="settings-card-icon" style="background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.2); color: #3b82f6;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="settings-card-title">
                                <h5>Thông tin cá nhân</h5>
                                <p>Cập nhật thông tin cơ bản của bạn</p>
                            </div>
                        </div>

                        <div class="settings-card-content">
                            <!-- Full Name -->
                            <div class="mb-4">
                                <label for="full_name" class="form-label fw-semibold">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    Họ và tên <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-glass"
                                       id="full_name"
                                       name="full_name"
                                       value="<?= e($currentUser['HoTen']) ?>"
                                       placeholder="Nhập họ và tên đầy đủ"
                                       required>
                            </div>

                            <!-- Email (Read-only) -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    Email
                                </label>
                                <div class="position-relative">
                                    <input type="email"
                                           class="form-glass"
                                           id="email"
                                           value="<?= e($currentUser['Email']) ?>"
                                           disabled
                                           style="padding-right: 120px;">

                                    <?php if (!empty($currentUser['email_verified_at'])): ?>
                                        <span class="position-absolute top-50 end-0 translate-middle-y me-3">
                                            <span class="badge" style="background: rgba(34, 197, 94, 0.2); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Đã xác thực
                                            </span>
                                        </span>
                                    <?php else: ?>
                                        <span class="position-absolute top-50 end-0 translate-middle-y me-3">
                                            <span class="badge" style="background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3);">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Chưa xác thực
                                            </span>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="settings-info-box mt-2">
                                    <i class="fas fa-info-circle"></i>
                                    <span>
                                        Email không thể thay đổi. Liên hệ admin nếu cần hỗ trợ.
                                        <?php if (empty($currentUser['email_verified_at'])): ?>
                                            <a href="<?= app_url('/resend-verification') ?>" class="text-primary text-decoration-none ms-2">
                                                <i class="fas fa-paper-plane me-1"></i>
                                                Gửi lại email xác thực
                                            </a>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                                
                            <!-- Birth Date & Gender Row -->
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label for="birth_date" class="form-label fw-semibold">
                                        <i class="fas fa-calendar me-2 text-primary"></i>
                                        Ngày sinh
                                    </label>
                                    <input type="date"
                                           class="form-glass"
                                           id="birth_date"
                                           name="birth_date"
                                           value="<?= e($currentUser['NgaySinh'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="gender" class="form-label fw-semibold">
                                        <i class="fas fa-venus-mars me-2 text-primary"></i>
                                        Giới tính
                                    </label>
                                    <select class="form-glass" id="gender" name="gender">
                                        <option value="">-- Chọn giới tính --</option>
                                        <option value="Nam" <?= ($currentUser['GioiTinh'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                        <option value="Nữ" <?= ($currentUser['GioiTinh'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                        <option value="Khác" <?= ($currentUser['GioiTinh'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
                                    </select>
                                </div>
                            </div>

                            <!-- CCCD -->
                            <div class="mb-4">
                                <label for="cccd" class="form-label fw-semibold">
                                    <i class="fas fa-id-card me-2 text-primary"></i>
                                    Căn cước công dân (CCCD)
                                </label>
                                <input type="text"
                                       class="form-glass"
                                       id="cccd"
                                       name="cccd"
                                       value="<?= e($currentUser['CCCD'] ?? '') ?>"
                                       placeholder="Nhập số CCCD (9-12 chữ số)"
                                       pattern="[0-9]{9,12}"
                                       title="CCCD phải có từ 9-12 chữ số">
                                <div class="settings-info-box mt-2">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Thông tin CCCD giúp xác thực danh tính của bạn</span>
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="mb-4">
                                <label for="phone" class="form-label fw-semibold">
                                    <i class="fas fa-phone me-2 text-primary"></i>
                                    Số điện thoại
                                </label>
                                <input type="tel"
                                       class="form-glass"
                                       id="phone"
                                       name="phone"
                                       value="<?= e($currentUser['SDT'] ?? '') ?>"
                                       placeholder="0987654321">
                            </div>

                            <!-- Address -->
                            <div class="mb-4">
                                <label for="address" class="form-label fw-semibold">
                                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                    Địa chỉ
                                </label>
                                <textarea class="form-glass"
                                          id="address"
                                          name="address"
                                          rows="3"
                                          placeholder="Nhập địa chỉ của bạn..."
                                          style="resize: vertical; min-height: 100px;"><?= e($currentUser['DiaChi'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Glass Morphism Action Buttons -->
                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-between align-items-center">
                        <a href="/profile" class="btn-glass order-2 order-md-1">
                            <i class="fas fa-arrow-left"></i>
                            <span>Quay lại</span>
                        </a>
                        <button type="submit" class="btn-glass-primary order-1 order-md-2">
                            <i class="fas fa-save"></i> 
                            <span>Lưu thay đổi</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Glass Morphism Sidebar -->
            <div class="glass-panel">
                <div class="settings-card-header">
                    <div class="settings-card-icon" style="background: rgba(34, 197, 94, 0.15); border-color: rgba(34, 197, 94, 0.2); color: #22c55e;">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="settings-card-title">
                        <h5>Thông tin tài khoản</h5>
                        <p>Chi tiết về tài khoản của bạn</p>
                    </div>
                </div>

                <div class="settings-card-content">
                    <!-- Username -->
                    <div class="settings-item mb-3">
                        <div class="settings-item-info">
                            <div class="settings-item-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="settings-item-text">
                                <h6>Tên đăng nhập</h6>
                                <small><?= e($currentUser['TenDN']) ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="settings-item mb-3">
                        <div class="settings-item-info">
                            <div class="settings-item-icon" style="background: rgba(168, 85, 247, 0.1); color: #a855f7;">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="settings-item-text">
                                <h6>Vai trò</h6>
                                <small>
                                    <?php
                                    switch ($currentUser['VaiTroID']) {
                                        case ROLE_ADMIN:
                                            echo '<span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Quản trị viên</span>';
                                            break;
                                        case ROLE_MODERATOR:
                                            echo '<span class="badge" style="background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3);">Điều hành viên</span>';
                                            break;
                                        case ROLE_SELLER:
                                            echo '<span class="badge" style="background: rgba(34, 197, 94, 0.2); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);">Seller</span>';
                                            break;
                                        default:
                                            echo '<span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3);">Thành viên</span>';
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Birth Date -->
                    <?php if (!empty($currentUser['NgaySinh'])): ?>
                        <div class="settings-item mb-3">
                            <div class="settings-item-info">
                                <div class="settings-item-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h6>Ngày sinh</h6>
                                    <small><?= formatDate($currentUser['NgaySinh']) ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Gender -->
                    <?php if (!empty($currentUser['GioiTinh'])): ?>
                        <div class="settings-item mb-3">
                            <div class="settings-item-info">
                                <div class="settings-item-icon" style="background: rgba(168, 85, 247, 0.1); color: #a855f7;">
                                    <i class="fas fa-venus-mars"></i>
                                </div>
                                <div class="settings-item-text">
                                    <h6>Giới tính</h6>
                                    <small><?= e($currentUser['GioiTinh']) ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Join Date -->
                    <div class="settings-item mb-4">
                        <div class="settings-item-info">
                            <div class="settings-item-icon" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="settings-item-text">
                                <h6>Ngày tham gia</h6>
                                <small><?= formatDate($currentUser['NgayTao'] ?? date('Y-m-d')) ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="d-grid gap-2">
                        <a href="/profile/change-password" class="btn-glass settings-item-warning">
                            <i class="fas fa-key"></i>
                            <span>Đổi mật khẩu</span>
                        </a>

                        <?php if ($currentUser['VaiTroID'] < ROLE_SELLER): ?>
                            <a href="/register-seller" class="btn-glass" style="background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.2); color: #22c55e;">
                                <i class="fas fa-store"></i>
                                <span>Đăng ký Seller</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="glass-container mt-4" style="background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.2); padding: 1.5rem;">
                    <div class="d-flex align-items-start">
                        <div class="glass-icon-sm me-3" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h6 class="mb-2">Bảo mật thông tin</h6>
                            <p class="mb-0 small">Thông tin cá nhân của bạn được bảo mật và chỉ hiển thị khi cần thiết.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../../includes/layouts/client/footer.php'; ?>

    <!-- Enhanced JavaScript for Glass Morphism Profile Edit -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Avatar upload functionality with enhanced preview
            const avatarInput = document.getElementById('avatar');
            const avatarPreview = document.getElementById('avatarPreview');
            const heroAvatar = document.getElementById('heroAvatar') || document.querySelector('.profile-hero .profile-avatar');
            const uploadOverlay = document.querySelector('.upload-overlay');

            if (avatarInput && avatarPreview) {
                // Show/hide upload overlay on hover
                const avatarContainer = document.querySelector('.avatar-upload-container');
                if (avatarContainer && uploadOverlay) {
                    avatarContainer.addEventListener('mouseenter', function() {
                        uploadOverlay.style.opacity = '1';
                    });

                    avatarContainer.addEventListener('mouseleave', function() {
                        uploadOverlay.style.opacity = '0';
                    });
                }

                // Handle file selection
                avatarInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const allowedExts = <?= json_encode(getUploadAllowedExtensionsArray()) ?>;
                        const maxBytes = <?= (int) getUploadMaxSizeBytes() ?>;
                        const ext = file.name.split('.').pop().toLowerCase();

                        if (!allowedExts.includes(ext)) {
                            alert('Loại file không được hỗ trợ. Chỉ chấp nhận: ' + allowedExts.join(', '));
                            avatarInput.value = '';
                            return;
                        }

                        if (file.size > maxBytes) {
                            alert('Kích thước file quá lớn. Tối đa: ' + '<?= formatFileSize(getUploadMaxSizeBytes()) ?>');
                            avatarInput.value = '';
                            return;
                        }

                        if (!file.type.startsWith('image/')) {
                            alert('Vui lòng chọn file hình ảnh hợp lệ.');
                            avatarInput.value = '';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            // Update both preview images with smooth transition
                            avatarPreview.style.opacity = '0.5';
                            if (heroAvatar) heroAvatar.style.opacity = '0.5';

                            setTimeout(() => {
                                avatarPreview.src = e.target.result;
                                if (heroAvatar) heroAvatar.src = e.target.result;

                                avatarPreview.style.opacity = '1';
                                if (heroAvatar) heroAvatar.style.opacity = '1';
                            }, 200);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        alert('Vui lòng chọn file hình ảnh hợp lệ (JPG, PNG, GIF).');
                        this.value = '';
                    }
                });
            }

            // Form validation with glass morphism styling
            const form = document.getElementById('profileEditForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const fullName = document.getElementById('full_name').value.trim();

                    if (!fullName) {
                        e.preventDefault();
                        showGlassAlert('Vui lòng nhập họ và tên!', 'error');
                        document.getElementById('full_name').focus();
                        return;
                    }

                    if (fullName.length < 2) {
                        e.preventDefault();
                        showGlassAlert('Họ và tên phải có ít nhất 2 ký tự!', 'error');
                        document.getElementById('full_name').focus();
                        return;
                    }

                    // Show loading state
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Đang lưu...</span>';
                    }
                });
            }

            // Enhanced form field interactions
            const formFields = document.querySelectorAll('.form-glass');
            formFields.forEach(field => {
                field.addEventListener('focus', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 8px 25px rgba(var(--primary-rgb), 0.15)';
                });

                field.addEventListener('blur', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });

            // Phone number formatting
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 10) value = value.slice(0, 10);
                    e.target.value = value;
                });
            }

            // CCCD formatting
            const cccdInput = document.getElementById('cccd');
            if (cccdInput) {
                cccdInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 12) value = value.slice(0, 12);
                    e.target.value = value;
                });
            }
        });

        // Glass morphism alert function
        function showGlassAlert(message, type = 'info') {
            const alertColors = {
                'error': 'rgba(239, 68, 68, 0.1)',
                'success': 'rgba(34, 197, 94, 0.1)',
                'info': 'rgba(59, 130, 246, 0.1)'
            };

            const iconColors = {
                'error': '#ef4444',
                'success': '#22c55e',
                'info': '#3b82f6'
            };

            const icons = {
                'error': 'fas fa-exclamation-circle',
                'success': 'fas fa-check-circle',
                'info': 'fas fa-info-circle'
            };

            const alert = document.createElement('div');
            alert.className = 'glass-container position-fixed';
            alert.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                background: ${alertColors[type]};
                border-color: ${iconColors[type]}40;
                padding: 1rem 1.5rem;
                max-width: 400px;
                animation: slideInRight 0.3s ease;
            `;

            alert.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="${icons[type]} me-3" style="color: ${iconColors[type]};"></i>
                    <span>${message}</span>
                    <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;

            document.body.appendChild(alert);

            setTimeout(() => {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>

    <style>
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .avatar-upload-container .upload-overlay {
            transition: opacity 0.3s ease;
        }

        .form-glass:focus {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Mobile responsive enhancements */
        @media (max-width: 768px) {
            .glass-grid-2 {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .profile-hero {
                padding: 2rem 0 1.5rem;
            }

            .profile-avatar {
                width: 100px !important;
                height: 100px !important;
            }

            .profile-info h2 {
                font-size: 1.5rem;
            }

            .settings-card {
                padding: 1.5rem;
                border-radius: 16px;
            }

            .btn-glass, .btn-glass-primary {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .profile-container {
                padding: 0 1rem;
            }

            .glass-container {
                border-radius: 12px;
            }

            .settings-card {
                padding: 1.25rem;
            }

            .row.mb-4 .col-md-6 {
                margin-bottom: 1rem;
            }
        }
    </style>

    