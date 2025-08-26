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
                <li class="breadcrumb-item active">Tạo bài đăng mới</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            Tạo bài đăng mới
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= e($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" id="createPostForm">
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
                                           value="<?= e($_POST['title'] ?? '') ?>"
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
                                              required><?= e($_POST['content'] ?? '') ?></textarea>
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
                                                        <?= ($_POST['category'] ?? '') == $category['ID'] ? 'selected' : '' ?>>
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
                                               value="<?= e($_POST['price'] ?? '') ?>"
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
                                               value="<?= e($_POST['area'] ?? '') ?>"
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
                                           value="<?= e($_POST['address'] ?? '') ?>"
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
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="ward" class="form-label">Phường/Xã</label>
                                        <select class="form-select" id="ward" name="ward">
                                            <option value="">Chọn phường/xã</option>
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
                                
                                <div class="upload-area" onclick="document.getElementById('images').click()">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h6>Kéo thả hoặc click để chọn hình ảnh</h6>
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

                            <div class="d-flex justify-content-between">
                                <a href="/seller/posts" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary">
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
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Hướng dẫn
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><i class="fas fa-check-circle text-success me-2"></i>Tiêu đề hấp dẫn</h6>
                            <small class="text-muted">Viết tiêu đề ngắn gọn, thu hút và mô tả đúng bài đăng</small>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-check-circle text-success me-2"></i>Mô tả chi tiết</h6>
                            <small class="text-muted">Cung cấp thông tin đầy đủ về tiện ích, quy định</small>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-check-circle text-success me-2"></i>Hình ảnh chất lượng</h6>
                            <small class="text-muted">Đăng nhiều hình ảnh rõ nét, đẹp để thu hút khách</small>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-check-circle text-success me-2"></i>Giá cả hợp lý</h6>
                            <small class="text-muted">Đặt giá phù hợp với thị trường và chất lượng</small>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Bài đăng sẽ được kiểm duyệt trước khi hiển thị công khai.
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
