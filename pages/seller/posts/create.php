<?php
/**
 * Create Post Page
 * Tro365 - Website thuê trọ
 */

// Load autoloader and configuration
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/functions/helpers.php';
require_once __DIR__ . '/../../../includes/functions/auth.php';
require_once __DIR__ . '/../../../includes/functions/validation.php';

use Tro365\Core\Auth;
use Tro365\Models\Post;
use Tro365\Services\Upload;
use Tro365\Core\Database;
use Tro365\Activity;

$auth = new Auth();
$post = new Post();
$db = Database::getInstance();

// Require seller role
$auth->requireSeller();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }
        
        $currentUser = $auth->getCurrentUser();

        // Debug user ID
        writeLog("Current user ID: " . $currentUser['ID']);

        // Debug POST data
        writeLog("POST data: " . json_encode($_POST));

        // Verify user exists in database
        $userExists = $db->selectOne("SELECT ID FROM KhachHang WHERE ID = :id", ['id' => $currentUser['ID']]);
        if (!$userExists) {
            throw new Exception('User không tồn tại trong database. Vui lòng đăng nhập lại.');
        }

        // Prepare location codes (API codes are strings)
        $provinceCode = trim($_POST['province'] ?? '');
        $districtCode = trim($_POST['district'] ?? '');
        $wardCode = trim($_POST['ward'] ?? '');

        // Debug location codes
        writeLog("Location codes - Province: $provinceCode, District: $districtCode, Ward: $wardCode");

        // Enhanced validation using rakit/validation
        $formData = [
            'title' => cleanInput($_POST['title'] ?? ''),
            'description' => sanitizeHtml($_POST['content'] ?? ''),
            'category_id' => (int)($_POST['category'] ?? 0),
            'price' => (int)($_POST['price'] ?? 0),
            'area' => (float)($_POST['area'] ?? 0),
            'rooms' => (int)($_POST['rooms'] ?? 1),
            'address' => cleanInput($_POST['address'] ?? ''),
            'province_id' => !empty($provinceCode) ? (int)$provinceCode : 0,
            'district_id' => !empty($districtCode) ? (int)$districtCode : 0,
            'ward_id' => !empty($wardCode) ? (int)$wardCode : 0
        ];

        $validationResult = \Tro365\Helpers\ValidationHelper::validatePostForm($formData);
        if (!$validationResult['valid']) {
            $errors = [];
            foreach ($validationResult['errors'] as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            throw new Exception(implode(', ', $errors));
        }

        $postData = [
            'TieuDe' => $formData['title'],
            'NoiDung' => $formData['description'],
            'Gia' => $formData['price'],
            'DienTich' => $formData['area'],
            'SoPhong' => $formData['rooms'],
            'DiaChi' => $formData['address'],
            'DanhMucID' => $formData['category_id'],
            'TinhThanhID' => !empty($provinceCode) ? $provinceCode : null,
            'QuanHuyenID' => !empty($districtCode) ? $districtCode : null,
            'XaPhuongID' => !empty($wardCode) ? $wardCode : null,
            'NguoiDangID' => $currentUser['ID'],
            'TrangThai' => POST_STATUS_PENDING
        ];

        // Validate room count against system limit
        $maxRooms = getMaxRoomsPerPost();
        if ($postData['SoPhong'] < 1 || $postData['SoPhong'] > $maxRooms) {
            throw new Exception("Số phòng phải từ 1 đến {$maxRooms} phòng");
        }

        // Create post
        $postId = $post->create($postData);

        // Log activity
        try {
            $activity = new Activity();
            $activity->log(
                $currentUser['ID'],
                'seller_post_created',
                'Tạo bài đăng mới: ' . $postData['TieuDe'],
                ['post_id' => $postId, 'post_title' => $postData['TieuDe']]
            );
        } catch (Exception $e) {
            // Silent fail for activity logging
        }

        // Handle image uploads
        writeLog("FILES data: " . json_encode($_FILES));

        if (!empty($_FILES['images']['name'][0])) {
            writeLog("Processing image uploads...");
            $upload = new Upload();
            $uploadResults = $upload->uploadMultiple($_FILES['images'], 'posts');

            writeLog("Upload results: " . json_encode($uploadResults));

            $imageOrder = 0;
            $uploadErrors = [];
            foreach ($uploadResults as $result) {
                if ($result['success']) {
                    writeLog("Adding image to post: " . $result['web_path']);
                    $post->addImage($postId, $result['web_path'], $imageOrder++);

                    // Set first image as main image
                    if ($imageOrder === 1) {
                        $post->update($postId, ['AnhDaiDien' => $result['web_path']]);
                    }
                } else {
                    $errorMsg = $result['error'] ?? 'Unknown error';
                    writeLog("Upload failed: " . $errorMsg);
                    $uploadErrors[] = $errorMsg;
                }
            }

            // Show upload errors to user
            if (!empty($uploadErrors)) {
                $error = "Một số ảnh không thể upload: " . implode(', ', $uploadErrors);
            }
        } else {
            writeLog("No images uploaded - FILES check failed");
        }
        
        setFlashMessage(MSG_SUCCESS, 'Tạo bài đăng thành công! Bài đăng đang chờ duyệt.');
        redirect('/seller/posts');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get form data
$categories = $post->getCategories();
// Provinces will be loaded via API in JavaScript
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạo bài đăng mới - <?= getWebsiteName() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/client/main.css" rel="stylesheet">
    <link href="/assets/css/client/glass-morphism.css" rel="stylesheet">
    <link href="/assets/css/client/layouts.css" rel="stylesheet">
    <style>
        /* Enhanced Glass Morphism Post Creation Styles */
        .post-creation-container {
            background: var(--glass-gradient);
            backdrop-filter: var(--backdrop-filter);
            border-radius: 24px;
            padding: 0;
            overflow: hidden;
            box-shadow: var(--glass-shadow-strong);
            border: 2px solid var(--glass-border);
        }

        [data-theme="light"] .post-creation-container {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] .post-creation-container {
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.15);
        }

        .post-creation-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem;
            margin: 0;
            border-radius: 22px 22px 0 0;
        }

        .post-creation-body {
            padding: 2rem;
        }

        .form-section-glass {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            border: 2px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            transition: var(--glass-transition);
        }

        [data-theme="light"] .form-section-glass {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(0, 0, 0, 0.08);
        }

        [data-theme="dark"] .form-section-glass {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .form-section-glass:hover {
            transform: translateY(-2px);
            box-shadow: var(--glass-shadow-strong);
            border-color: var(--glass-border-strong);
        }

        .section-title-glass {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.25rem;
        }

        .section-title-glass i {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .form-control-glass {
            background: var(--glass-bg-light);
            backdrop-filter: blur(8px);
            border: 2px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.875rem 1rem;
            transition: var(--glass-transition);
            color: var(--text-primary);
        }

        [data-theme="light"] .form-control-glass {
            background: rgba(255, 255, 255, 0.7);
            border: 2px solid rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] .form-control-glass {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .form-control-glass:focus {
            background: var(--glass-bg);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: none;
        }

        .upload-area-glass {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            border: 3px dashed var(--glass-border);
            border-radius: 20px;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--glass-transition);
            position: relative;
            overflow: hidden;
        }

        [data-theme="light"] .upload-area-glass {
            background: rgba(255, 255, 255, 0.6);
            border-color: rgba(0, 0, 0, 0.15);
        }

        [data-theme="dark"] .upload-area-glass {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .upload-area-glass:hover {
            border-color: var(--primary-color);
            background: var(--glass-bg-strong);
            transform: translateY(-2px);
            box-shadow: var(--glass-shadow-strong);
        }

        .upload-area-glass.dragover {
            border-color: var(--primary-color);
            background: var(--glass-bg-strong);
            transform: scale(1.02);
        }

        .upload-area-glass::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.3) 50%, transparent 100%);
        }

        .btn-glass-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: 2px solid transparent;
            border-radius: 12px;
            color: white;
            padding: 0.875rem 2rem;
            font-weight: 600;
            transition: var(--glass-transition);
            position: relative;
            overflow: hidden;
        }

        .btn-glass-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.5) 50%, transparent 100%);
        }

        .btn-glass-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            filter: brightness(1.1);
        }

        .btn-glass-secondary {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            border: 2px solid var(--glass-border);
            border-radius: 12px;
            color: var(--text-primary);
            padding: 0.875rem 2rem;
            font-weight: 500;
            transition: var(--glass-transition);
        }

        [data-theme="light"] .btn-glass-secondary {
            background: rgba(255, 255, 255, 0.8);
            border-color: rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] .btn-glass-secondary {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .btn-glass-secondary:hover {
            background: var(--glass-bg-strong);
            border-color: var(--glass-border-strong);
            transform: translateY(-2px);
            color: var(--text-primary);
        }

        .sidebar-glass {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            border: 2px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        [data-theme="light"] .sidebar-glass {
            background: rgba(255, 255, 255, 0.8);
            border-color: rgba(0, 0, 0, 0.08);
        }

        [data-theme="dark"] .sidebar-glass {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .help-item-glass {
            background: var(--glass-bg-light);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: var(--glass-transition);
        }

        [data-theme="light"] .help-item-glass {
            background: rgba(255, 255, 255, 0.6);
            border-color: rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] .help-item-glass {
            background: rgba(255, 255, 255, 0.02);
            border-color: rgba(255, 255, 255, 0.08);
        }

        .help-item-glass:hover {
            background: var(--glass-bg);
            border-color: var(--glass-border-strong);
            transform: translateX(4px);
        }

        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .image-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid var(--glass-border);
            transition: var(--glass-transition);
        }

        .image-preview img:hover {
            transform: scale(1.05);
            border-color: var(--primary-color);
        }

        /* Mobile-first responsive design */
        @media (max-width: 768px) {
            .post-creation-container {
                margin: 1rem;
                border-radius: 20px;
            }

            .post-creation-header {
                padding: 1.5rem;
                border-radius: 18px 18px 0 0;
            }

            .post-creation-body {
                padding: 1.5rem;
            }

            .form-section-glass {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .upload-area-glass {
                padding: 2rem 1rem;
            }

            .sidebar-glass {
                margin-top: 2rem;
                position: static;
            }
        }

        @media (max-width: 480px) {
            .post-creation-container {
                margin: 0.5rem;
                border-radius: 16px;
            }

            .post-creation-header {
                padding: 1rem;
                border-radius: 14px 14px 0 0;
            }

            .post-creation-body {
                padding: 1rem;
            }

            .form-section-glass {
                padding: 1rem;
                border-radius: 16px;
            }

            .section-title-glass {
                font-size: 1.1rem;
            }

            .btn-glass-primary,
            .btn-glass-secondary {
                padding: 0.75rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../includes/layouts/client/header.php'; ?>

    <div class="container my-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="/seller">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/seller/posts">Quản lý bài đăng</a></li>
                <li class="breadcrumb-item active">Tạo bài đăng mới</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <div class="post-creation-container">
                    <div class="post-creation-header">
                        <h4 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            Tạo bài đăng mới
                        </h4>
                        <p class="mb-0 mt-2 opacity-75">Tạo bài đăng chất lượng để thu hút khách hàng</p>
                    </div>
                    <div class="post-creation-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= e($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" id="createPostForm">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                            <!-- Basic Information -->
                            <div class="form-section-glass">
                                <div class="section-title-glass">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Thông tin cơ bản</span>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label fw-semibold">
                                        Tiêu đề <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control form-control-glass"
                                           id="title"
                                           name="title"
                                           value="<?= e($_POST['title'] ?? '') ?>"
                                           placeholder="Nhập tiêu đề bài đăng..."
                                           required>
                                </div>
                                
                                <!-- Mô tả ngắn field removed - content will be used for both full content and auto-generated excerpts -->
                                
                                <div class="mb-3">
                                    <label for="content" class="form-label fw-semibold">Mô tả chi tiết <span class="text-danger">*</span></label>
                                    <textarea class="form-control form-control-glass"
                                              id="content"
                                              name="content"
                                              rows="6"
                                              placeholder="Mô tả chi tiết về phòng trọ, tiện ích, quy định..."
                                              required><?= e($_POST['content'] ?? '') ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="category" class="form-label fw-semibold">
                                            Danh mục <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-control-glass" id="category" name="category" required>
                                            <option value="">Chọn danh mục</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['ID'] ?>"
                                                        <?= ($_POST['category'] ?? '') == $category['ID'] ? 'selected' : '' ?>>
                                                    <?= e($category['TenDM']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="price" class="form-label fw-semibold">
                                            Giá thuê (VNĐ/tháng) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number"
                                               class="form-control form-control-glass"
                                               id="price"
                                               name="price"
                                               value="<?= e($_POST['price'] ?? '') ?>"
                                               placeholder="0"
                                               min="0"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="area" class="form-label fw-semibold">
                                            Diện tích (m²) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number"
                                               class="form-control form-control-glass"
                                               id="area"
                                               name="area"
                                               value="<?= e($_POST['area'] ?? '') ?>"
                                               placeholder="0"
                                               step="0.1"
                                               min="0"
                                               required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <?php $maxRooms = getMaxRoomsPerPost(); ?>
                                        <label for="rooms" class="form-label fw-semibold">
                                            Số phòng
                                            <span class="text-muted">(tối đa <?= $maxRooms ?>)</span>
                                        </label>
                                        <input type="number"
                                               class="form-control form-control-glass"
                                               id="rooms"
                                               name="rooms"
                                               value="<?= e($_POST['rooms'] ?? '1') ?>"
                                               min="1"
                                               max="<?= $maxRooms ?>"
                                               required>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Số phòng có thể đăng từ 1 đến <?= $maxRooms ?> phòng.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Location -->
                            <div class="form-section-glass">
                                <div class="section-title-glass">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Địa điểm</span>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label fw-semibold">
                                        Địa chỉ cụ thể <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control form-control-glass"
                                           id="address"
                                           name="address"
                                           value="<?= e($_POST['address'] ?? '') ?>"
                                           placeholder="Số nhà, tên đường..."
                                           required>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="province" class="form-label fw-semibold">Tỉnh/Thành phố</label>
                                        <select class="form-select form-control-glass" id="province" name="province">
                                            <option value="">Chọn tỉnh/thành</option>
                                            <!-- Provinces will be loaded via API -->
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="district" class="form-label fw-semibold">Quận/Huyện</label>
                                        <select class="form-select form-control-glass" id="district" name="district">
                                            <option value="">Chọn quận/huyện</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="ward" class="form-label fw-semibold">Phường/Xã</label>
                                        <select class="form-select form-control-glass" id="ward" name="ward">
                                            <option value="">Chọn phường/xã</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Images -->
                            <div class="form-section-glass">
                                <div class="section-title-glass">
                                    <i class="fas fa-images"></i>
                                    <span>Hình ảnh</span>
                                </div>

                                <div class="upload-area-glass" onclick="document.getElementById('images').click()">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                    <h6 class="fw-semibold">Kéo thả hoặc click để chọn hình ảnh</h6>
                                    <p class="text-muted mb-0">
                                        Hỗ trợ: JPG, PNG, GIF. Tối đa <?= formatFileSize(UPLOAD_MAX_SIZE) ?> mỗi file.
                                    </p>
                                </div>

                                <input type="file"
                                       id="images"
                                       name="images[]"
                                       multiple
                                       accept="image/*"
                                       style="display: none;">

                                <div id="imagePreview" class="image-preview"></div>
                            </div>

                            <div class="d-flex justify-content-between flex-wrap gap-3 mt-4">
                                <a href="/seller/posts" class="btn btn-glass-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Quay lại
                                </a>
                                <button type="submit" class="btn btn-glass-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Tạo bài đăng
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sidebar-glass">
                    <div class="section-title-glass mb-4">
                        <i class="fas fa-lightbulb"></i>
                        <span>Hướng dẫn</span>
                    </div>

                    <div class="help-item-glass">
                        <h6 class="fw-semibold mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Tiêu đề hấp dẫn
                        </h6>
                        <small class="text-muted">Viết tiêu đề ngắn gọn, thu hút và mô tả đúng bài đăng</small>
                    </div>

                    <div class="help-item-glass">
                        <h6 class="fw-semibold mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Mô tả chi tiết
                        </h6>
                        <small class="text-muted">Cung cấp thông tin đầy đủ về tiện ích, quy định</small>
                    </div>

                    <div class="help-item-glass">
                        <h6 class="fw-semibold mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Hình ảnh chất lượng
                        </h6>
                        <small class="text-muted">Đăng nhiều hình ảnh rõ nét, đẹp để thu hút khách</small>
                    </div>

                    <div class="help-item-glass">
                        <h6 class="fw-semibold mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Giá cả hợp lý
                        </h6>
                        <small class="text-muted">Đặt giá phù hợp với thị trường và chất lượng</small>
                    </div>

                    <hr class="my-4" style="border-color: var(--glass-border);">

                    <div class="help-item-glass" style="border-color: var(--primary-color); background: rgba(102, 126, 234, 0.1);">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-info-circle text-primary mt-1"></i>
                            <div>
                                <strong class="text-primary">Lưu ý:</strong>
                                <small class="d-block text-muted mt-1">
                                    Bài đăng sẽ được kiểm duyệt trước khi hiển thị công khai.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../../includes/layouts/client/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store selected values for restoration
        const selectedProvince = '<?= $_POST['province'] ?? '' ?>';
        const selectedDistrict = '<?= $_POST['district'] ?? '' ?>';
        const selectedWard = '<?= $_POST['ward'] ?? '' ?>';

        // Image upload preview
        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            Array.from(e.target.files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
        
        // Drag and drop
        const uploadArea = document.querySelector('.upload-area-glass');
        
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
            
            const files = e.dataTransfer.files;
            document.getElementById('images').files = files;
            
            // Trigger change event
            const event = new Event('change', { bubbles: true });
            document.getElementById('images').dispatchEvent(event);
        });
        
        // Location cascading dropdowns
        document.getElementById('province').addEventListener('change', function() {
            const provinceId = this.value;
            const districtSelect = document.getElementById('district');
            const wardSelect = document.getElementById('ward');

            // Clear districts and wards
            districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
            wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';

            if (provinceId) {
                console.log('Loading districts for province:', provinceId);
                fetch(`/api/locations/districts?province_id=${provinceId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Districts loaded:', data);
                        if (Array.isArray(data)) {
                            data.forEach(district => {
                                const option = document.createElement('option');
                                option.value = district.ID;
                                option.textContent = district.TenQH;
                                districtSelect.appendChild(option);
                            });
                        } else {
                            console.error('Invalid districts data:', data);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading districts:', error);
                        alert('Lỗi tải danh sách quận/huyện. Vui lòng thử lại.');
                    });
            }
        });

        document.getElementById('district').addEventListener('change', function() {
            const districtId = this.value;
            const wardSelect = document.getElementById('ward');

            // Clear wards
            wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';

            if (districtId) {
                console.log('Loading wards for district:', districtId);
                fetch(`/api/locations/wards?district_id=${districtId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Wards loaded:', data);
                        if (Array.isArray(data)) {
                            data.forEach(ward => {
                                const option = document.createElement('option');
                                option.value = ward.ID;
                                option.textContent = ward.TenXP;
                                wardSelect.appendChild(option);
                            });
                        } else {
                            console.error('Invalid wards data:', data);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading wards:', error);
                        alert('Lỗi tải danh sách phường/xã. Vui lòng thử lại.');
                    });
            }
        });

        // Load provinces and restore selected values on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Load provinces first
            Tro365Common.loadProvinces('province', selectedProvince);

            if (selectedProvince) {

                // Load districts for selected province
                if (selectedDistrict) {
                    fetch(`/api/locations/districts?province_id=${selectedProvince}`)
                        .then(response => response.json())
                        .then(data => {
                            const districtSelect = document.getElementById('district');
                            data.forEach(district => {
                                const option = document.createElement('option');
                                option.value = district.ID;
                                option.textContent = district.TenQH;
                                option.selected = district.ID == selectedDistrict;
                                districtSelect.appendChild(option);
                            });

                            // Load wards for selected district
                            if (selectedWard) {
                                fetch(`/api/locations/wards?district_id=${selectedDistrict}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        const wardSelect = document.getElementById('ward');
                                        data.forEach(ward => {
                                            const option = document.createElement('option');
                                            option.value = ward.ID;
                                            option.textContent = ward.TenXP;
                                            option.selected = ward.ID == selectedWard;
                                            wardSelect.appendChild(option);
                                        });
                                    });
                            }
                        });
                }
            }
        });
    </script>

    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/<?= e(getTinyMCEApiKey()) ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        // Wait for DOM to be ready
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure element exists before initializing
            const contentElement = document.getElementById('content');
            if (!contentElement) {
                console.error('TinyMCE target element #content not found');
                return;
            }

            tinymce.init({
                selector: '#content',
                height: 300,
                menubar: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | ' +
                    'bold italic forecolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'removeformat | help',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
                language: 'vi',
                branding: false,
                promotion: false,
                // Disable analytics and tracking to prevent ERR_BLOCKED_BY_CLIENT
                analytics: false,
                usage_analytics: false,
                // TinyMCE 8 compatibility
                license_key: 'gpl',
                setup: function (editor) {
                    editor.on('change', function () {
                        editor.save();
                    });

                    // Handle initialization errors
                    editor.on('InitError', function(e) {
                        console.error('TinyMCE initialization error:', e);
                    });
                }
            }).catch(function(error) {
                console.error('TinyMCE initialization failed:', error);
            });
        });
    </script>
</body>
</html>
