<?php
/**
 * Edit Post Page
 * Tro365 - Website thuê trọ
 */

// Load autoloader and configuration
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../config/constants.php';

// Performance optimization includes
require_once __DIR__ . '/../../../includes/performance/optimization.php';

require_once __DIR__ . '/../../../includes/functions/helpers.php';
require_once __DIR__ . '/../../../includes/functions/auth.php';

use Tro365\Core\Auth;
use Tro365\Models\Post;
use Tro365\Services\Upload;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();
$post = new Post();

// Require seller, moderator, or admin role
if (!$auth->isSeller() && !$auth->isModerator() && !$auth->isAdmin()) {
    setFlashMessage(MSG_ERROR, 'Bạn không có quyền truy cập trang này');
    redirect('/');
}

$currentUser = $auth->getCurrentUser();
$postId = (int)($id ?? $_GET['id'] ?? 0);

if (!$postId) {
    setFlashMessage(MSG_ERROR, 'ID bài đăng không hợp lệ');
    redirect('/seller/posts');
}

// Get post data with caching for better performance
$postDataCacheKey = "post_edit_data_" . $postId;
$postData = cache_get($postDataCacheKey);

if ($postData === null) {
    $postData = $post->getById($postId);

    if ($postData) {
        // Cache post data for 5 minutes
        cache_set($postDataCacheKey, $postData, 300);
    }
}

if (!$postData) {
    setFlashMessage(MSG_ERROR, 'Bài đăng không tồn tại');
    redirect('/seller/posts');
}

// Check permission
if (!$post->canEdit($postId, $currentUser['ID'], $currentUser['VaiTroID'])) {
    setFlashMessage(MSG_ERROR, 'Bạn không có quyền chỉnh sửa bài đăng này');
    redirect('/seller/posts');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }
        
        // Enhanced validation using rakit/validation
        $formData = [
            'title' => cleanInput($_POST['title'] ?? ''),
            'description' => sanitizeHtml($_POST['content'] ?? ''),
            'category_id' => (int)($_POST['category'] ?? 0),
            'price' => (int)($_POST['price'] ?? 0),
            'area' => (float)($_POST['area'] ?? 0),
            'rooms' => (int)($_POST['rooms'] ?? 1),
            'address' => cleanInput($_POST['address'] ?? ''),
            'province_id' => (int)(trim($_POST['province'] ?? '') ?: 0),
            'district_id' => (int)(trim($_POST['district'] ?? '') ?: 0),
            'ward_id' => (int)(trim($_POST['ward'] ?? '') ?: 0)
        ];

        $validation = \Tro365\Helpers\ValidationHelper::validatePostForm($formData);
        if (!$validation['valid']) {
            $errors = [];
            foreach ($validation['errors'] as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            throw new Exception(implode(', ', $errors));
        }

        // Additional room count validation against system limit
        $maxRooms = getMaxRoomsPerPost();
        if ($formData['rooms'] > $maxRooms) {
            throw new Exception("Số phòng không được vượt quá {$maxRooms} phòng");
        }

        $updateData = [
            'TieuDe' => $formData['title'],
            'NoiDung' => $formData['description'],
            'Gia' => $formData['price'],
            'DienTich' => $formData['area'],
            'SoPhong' => $formData['rooms'],
            'DiaChi' => $formData['address'],
            'DanhMucID' => $formData['category_id'],
            'TinhThanhID' => trim($_POST['province'] ?? '') ?: null,
            'QuanHuyenID' => trim($_POST['district'] ?? '') ?: null,
            'XaPhuongID' => trim($_POST['ward'] ?? '') ?: null
        ];
        
        // Update post
        $post->update($postId, $updateData);
        
        // Handle new image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $upload = new Upload();
            $uploadResults = $upload->uploadMultiple($_FILES['images'], 'posts');
            
            $existingImages = $post->getImages($postId);
            $imageOrder = count($existingImages);
            
            $uploadErrors = [];
            foreach ($uploadResults as $result) {
                if ($result['success']) {
                    $post->addImage($postId, $result['web_path'], $imageOrder++);

                    // Set as main image if no main image exists
                    if (empty($postData['AnhDaiDien'])) {
                        $post->update($postId, ['AnhDaiDien' => $result['web_path']]);
                    }
                } else {
                    $errorMsg = $result['error'] ?? 'Unknown error';
                    $uploadErrors[] = $errorMsg;
                }
            }

            // Show upload errors to user
            if (!empty($uploadErrors)) {
                $error = "Một số ảnh không thể upload: " . implode(', ', $uploadErrors);
            }
        }
        
        setFlashMessage(MSG_SUCCESS, 'Cập nhật bài đăng thành công!');
        redirect('/seller/posts');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get form data with caching for better performance
$categoriesCacheKey = "post_edit_categories";
$categories = cache_get($categoriesCacheKey);

if ($categories === null) {
    $categories = $post->getCategories();
    // Cache categories for 10 minutes
    cache_set($categoriesCacheKey, $categories, 600);
}

// Provinces will be loaded via API in JavaScript (already cached in LocationService)

// Get existing images with caching
$existingImagesCacheKey = "post_edit_images_" . $postId;
$existingImages = cache_get($existingImagesCacheKey);

if ($existingImages === null) {
    $existingImages = $post->getImages($postId);
    // Cache images for 5 minutes
    cache_set($existingImagesCacheKey, $existingImages, 300);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa bài đăng - <?= getWebsiteName() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/client/main.css" rel="stylesheet">
    <style>
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
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
            border-radius: 5px;
            border: 2px solid #ddd;
        }
        .existing-image {
            position: relative;
            display: inline-block;
        }
        .existing-image img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }
        .remove-image {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            font-size: 12px;
            cursor: pointer;
        }
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        .upload-area:hover {
            border-color: #667eea;
        }
        .upload-area.dragover {
            border-color: #667eea;
            background-color: #f0f8ff;
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
                <li class="breadcrumb-item active">Chỉnh sửa bài đăng</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Chỉnh sửa bài đăng
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= e($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" id="editPostForm">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                            <!-- Basic Information -->
                            <div class="form-section">
                                <h5 class="mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Thông tin cơ bản
                                </h5>
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">
                                        Tiêu đề <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="title" 
                                           name="title" 
                                           value="<?= e($postData['TieuDe']) ?>"
                                           placeholder="Nhập tiêu đề bài đăng..."
                                           required>
                                </div>
                                
                                <!-- Mô tả ngắn field removed - content will be used for both full content and auto-generated excerpts -->
                                
                                <div class="mb-3">
                                    <label for="content" class="form-label">Mô tả chi tiết <span class="text-danger">*</span></label>
                                    <textarea class="form-control"
                                              id="content"
                                              name="content"
                                              rows="6"
                                              placeholder="Mô tả chi tiết về phòng trọ, tiện ích, quy định..."
                                              required><?= e($postData['NoiDung']) ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="category" class="form-label">
                                            Danh mục <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="category" name="category" required>
                                            <option value="">Chọn danh mục</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['ID'] ?>" 
                                                        <?= $postData['DanhMucID'] == $category['ID'] ? 'selected' : '' ?>>
                                                    <?= e($category['TenDM']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="price" class="form-label">
                                            Giá thuê (VNĐ/tháng) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="price" 
                                               name="price" 
                                               value="<?= $postData['Gia'] ?>"
                                               placeholder="0"
                                               min="0"
                                               required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="area" class="form-label">
                                            Diện tích (m²) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="area" 
                                               name="area" 
                                               value="<?= $postData['DienTich'] ?>"
                                               placeholder="0"
                                               step="0.1"
                                               min="0"
                                               required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <?php $maxRooms = getMaxRoomsPerPost(); ?>
                                        <label for="rooms" class="form-label">
                                            Số phòng
                                            <span class="text-muted">(tối đa <?= $maxRooms ?>)</span>
                                        </label>
                                        <input type="number"
                                               class="form-control"
                                               id="rooms"
                                               name="rooms"
                                               value="<?= $postData['SoPhong'] ?>"
                                               min="1"
                                               max="<?= $maxRooms ?>">
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Số phòng có thể đăng từ 1 đến <?= $maxRooms ?> phòng.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Location -->
                            <div class="form-section">
                                <h5 class="mb-3">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    Địa điểm
                                </h5>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">
                                        Địa chỉ cụ thể <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="address" 
                                           name="address" 
                                           value="<?= e($postData['DiaChi']) ?>"
                                           placeholder="Số nhà, tên đường..."
                                           required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="province" class="form-label">Tỉnh/Thành phố</label>
                                        <select class="form-select" id="province" name="province">
                                            <option value="">Chọn tỉnh/thành</option>
                                            <!-- Provinces will be loaded via API -->
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="district" class="form-label">Quận/Huyện</label>
                                        <select class="form-select" id="district" name="district">
                                            <option value="">Chọn quận/huyện</option>
                                            <!-- Districts will be loaded via API -->
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="ward" class="form-label">Phường/Xã</label>
                                        <select class="form-select" id="ward" name="ward">
                                            <option value="">Chọn phường/xã</option>
                                            <!-- Wards will be loaded via API -->
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Images -->
                            <div class="form-section">
                                <h5 class="mb-3">
                                    <i class="fas fa-images me-2"></i>
                                    Hình ảnh
                                </h5>
                                
                                <!-- Existing Images -->
                                <?php if (!empty($existingImages)): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Hình ảnh hiện tại</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($existingImages as $image): ?>
                                                <div class="existing-image">
                                                    <img src="<?= e($image['DuongDan']) ?>" alt="Hình ảnh bài đăng">
                                                    <button type="button" 
                                                            class="remove-image" 
                                                            onclick="removeImage(<?= $image['ID'] ?>)"
                                                            title="Xóa hình ảnh">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Upload New Images -->
                                <div class="upload-area" onclick="document.getElementById('images').click()">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h6>Thêm hình ảnh mới</h6>
                                    <p class="text-muted mb-0">
                                        Kéo thả hoặc click để chọn hình ảnh
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

                            <div class="d-flex justify-content-between">
                                <a href="/seller/posts" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Cập nhật bài đăng
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Thông tin bài đăng
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Trạng thái:</strong>
                            <?php
                            $statusClass = '';
                            $statusText = '';
                            switch ($postData['TrangThai']) {
                                case POST_STATUS_PENDING:
                                    $statusClass = 'bg-warning';
                                    $statusText = 'Chờ duyệt';
                                    break;
                                case POST_STATUS_APPROVED:
                                    $statusClass = 'bg-success';
                                    $statusText = 'Đã duyệt';
                                    break;
                                case POST_STATUS_REJECTED:
                                    $statusClass = 'bg-danger';
                                    $statusText = 'Từ chối';
                                    break;
                                case POST_STATUS_HIDDEN:
                                    $statusClass = 'bg-secondary';
                                    $statusText = 'Ẩn';
                                    break;
                            }
                            ?>
                            <span class="badge <?= $statusClass ?>">
                                <?= $statusText ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Ngày tạo:</strong><br>
                            <?= formatDateTime($postData['NgayTao']) ?>
                        </div>
                        
                        <div class="mb-3">
                            <strong>Lượt xem:</strong><br>
                            <?= number_format($postData['LuotXem']) ?>
                        </div>
                        
                        <?php if ($postData['NgayDuyet']): ?>
                            <div class="mb-3">
                                <strong>Ngày duyệt:</strong><br>
                                <?= formatDateTime($postData['NgayDuyet']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <a href="/post/<?= $postData['ID'] ?>" 
                               class="btn btn-outline-primary btn-sm"
                               target="_blank">
                                <i class="fas fa-eye me-2"></i>
                                Xem bài đăng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../../includes/layouts/client/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
        const uploadArea = document.querySelector('.upload-area');
        
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
        
        // Load provinces and restore selected values
        document.addEventListener('DOMContentLoaded', function() {
            const selectedProvince = '<?= $postData['TinhThanhID'] ?? '' ?>';
            const selectedDistrict = '<?= $postData['QuanHuyenID'] ?? '' ?>';
            const selectedWard = '<?= $postData['XaPhuongID'] ?? '' ?>';

            // Load provinces first
            Tro365Common.loadProvinces('province', selectedProvince);

            // Load districts if province is selected
            if (selectedProvince) {
                setTimeout(() => {
                    fetch(`/api/locations/districts?province_id=${selectedProvince}`)
                        .then(response => response.json())
                        .then(response => {
                            const districtSelect = document.getElementById('district');
                            // Handle API response format {success: true, data: [...]}
                            const data = response.data || response;
                            if (Array.isArray(data)) {
                                data.forEach(district => {
                                    const option = document.createElement('option');
                                    option.value = district.ID;
                                    option.textContent = district.TenQH;
                                    option.selected = district.ID == selectedDistrict;
                                    districtSelect.appendChild(option);
                                });
                            } else {
                                console.warn('Districts data is not an array:', response);
                            }

                            // Load wards if district is selected
                            if (selectedDistrict) {
                                fetch(`/api/locations/wards?district_id=${selectedDistrict}`)
                                    .then(response => response.json())
                                    .then(response => {
                                        const wardSelect = document.getElementById('ward');
                                        // Handle API response format {success: true, data: [...]}
                                        const data = response.data || response;
                                        if (Array.isArray(data)) {
                                            data.forEach(ward => {
                                                const option = document.createElement('option');
                                                option.value = ward.ID;
                                                option.textContent = ward.TenXP;
                                                option.selected = ward.ID == selectedWard;
                                                wardSelect.appendChild(option);
                                            });
                                        } else {
                                            console.warn('Wards data is not an array:', response);
                                        }
                                    });
                            }
                        });
                }, 500); // Wait for provinces to load
            }
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
                fetch(`/api/locations/districts?province_id=${provinceId}`)
                    .then(response => response.json())
                    .then(response => {
                        // Handle API response format {success: true, data: [...]}
                        const data = response.data || response;
                        if (Array.isArray(data)) {
                            data.forEach(district => {
                                const option = document.createElement('option');
                                option.value = district.ID;
                                option.textContent = district.TenQH;
                                districtSelect.appendChild(option);
                            });
                        } else {
                            console.warn('Districts data is not an array:', response);
                        }
                    });
            }
        });
        
        document.getElementById('district').addEventListener('change', function() {
            const districtId = this.value;
            const wardSelect = document.getElementById('ward');
            
            // Clear wards
            wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
            
            if (districtId) {
                fetch(`/api/locations/wards?district_id=${districtId}`)
                    .then(response => response.json())
                    .then(response => {
                        // Handle API response format {success: true, data: [...]}
                        const data = response.data || response;
                        if (Array.isArray(data)) {
                            data.forEach(ward => {
                                const option = document.createElement('option');
                                option.value = ward.ID;
                                option.textContent = ward.TenXP;
                                wardSelect.appendChild(option);
                            });
                        } else {
                            console.warn('Wards data is not an array:', response);
                        }
                    });
            }
        });
        
        // Remove image function
        function removeImage(imageId) {
            if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')) {
                // Show loading state
                const button = document.querySelector(`button[onclick="removeImage(${imageId})"]`);
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.disabled = true;

                fetch('/api/posts/remove-image', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('input[name="csrf_token"]').value
                    },
                    body: JSON.stringify({ image_id: imageId })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Remove image element from DOM
                        button.closest('.existing-image').remove();

                        // Show success message
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show';
                        alert.innerHTML = `
                            ${data.message || 'Xóa hình ảnh thành công!'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        document.querySelector('.form-section').insertBefore(alert, document.querySelector('.form-section').firstChild);

                        // Auto dismiss after 3 seconds
                        setTimeout(() => {
                            alert.remove();
                        }, 3000);
                    } else {
                        throw new Error(data.error || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Remove image error:', error);
                    alert('Có lỗi xảy ra khi xóa hình ảnh: ' + error.message);

                    // Restore button state
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }
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
                // Disable all tracking and telemetry
                telemetry: false,
                tracking: false,
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
