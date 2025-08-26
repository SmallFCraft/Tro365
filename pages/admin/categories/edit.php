<?php
/**
 * Admin Categories Edit
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;
use Tro365\Category;
use Tro365\Activity;

$auth = new Auth();
$category = new Category();

// Require admin role
$auth->requireAdmin();

$currentUser = $auth->getCurrentUser();
$error = '';
$success = '';

// Get category ID
$categoryId = (int)($_GET['id'] ?? 0);
if (!$categoryId) {
    redirect('/admin/categories');
    exit;
}

// Get category data
$categoryData = $category->getById($categoryId);
if (!$categoryData) {
    setFlashMessage(MSG_ERROR, 'Không tìm thấy danh mục');
    redirect('/admin/categories');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }
        
        // Enhanced validation using rakit/validation
        $formData = [
            'name' => trim($_POST['ten_dm'] ?? ''),
            'description' => trim($_POST['mo_ta'] ?? ''),
            'order' => (int)($_POST['thu_tu'] ?? 0),
            'status' => (int)($_POST['trang_thai'] ?? 1)
        ];

        $validation = \Tro365\Helpers\ValidationHelper::enhancedValidate($formData, [
            'name' => 'required|min:2|max:100',
            'description' => 'nullable|max:500',
            'order' => 'integer|min:0|max:999',
            'status' => 'required|integer|in:0,1'
        ], [
            'name.required' => 'Vui lòng nhập tên danh mục',
            'name.min' => 'Tên danh mục phải có ít nhất 2 ký tự',
            'name.max' => 'Tên danh mục không được vượt quá 100 ký tự',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự',
            'order.integer' => 'Thứ tự phải là số nguyên',
            'order.min' => 'Thứ tự không được nhỏ hơn 0',
            'order.max' => 'Thứ tự không được lớn hơn 999',
            'status.required' => 'Vui lòng chọn trạng thái',
            'status.integer' => 'Trạng thái không hợp lệ',
            'status.in' => 'Trạng thái không hợp lệ'
        ]);

        if (!$validation['valid']) {
            $errors = [];
            foreach ($validation['errors'] as $field => $fieldErrors) {
                $errors = array_merge($errors, $fieldErrors);
            }
            throw new Exception(implode(', ', $errors));
        }

        $data = [
            'TenDM' => $formData['name'],
            'MoTa' => $formData['description'],
            'ThuTu' => $formData['order'],
            'TrangThai' => $formData['status']
        ];
        
        $category->update($categoryId, $data);
        
        // Log activity
        try {
            $activity = new Activity();
            $activity->log($currentUser['ID'], 'update_category', 'Cập nhật danh mục: ' . $data['TenDM'], ['category_id' => $categoryId]);
        } catch (Exception $e) {
            writeLog("Activity log error: " . $e->getMessage());
        }
        
        $success = 'Cập nhật danh mục thành công!';
        
        // Refresh category data
        $categoryData = $category->getById($categoryId);
        
        // Redirect to categories list after 2 seconds
        header("refresh:2;url=/admin/categories");
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Chỉnh sửa danh mục: ' . $categoryData['TenDM'];
$pageDescription = 'Chỉnh sửa thông tin danh mục bài đăng';

include_once __DIR__ . '/../../../includes/layouts/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="admin-sidebar">
                <?php include __DIR__ . '/../../../includes/layouts/admin/sidebar.php'; ?>
            </div>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-10 main-content">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="/admin">
                            <i class="fas fa-home me-1"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="/admin/categories">
                            <i class="fas fa-tags me-1"></i>
                            Quản lý danh mục
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <i class="fas fa-edit me-1"></i>
                        Chỉnh sửa: <?= e($categoryData['TenDM']) ?>
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-edit me-3"></i>
                            Chỉnh sửa danh mục
                        </h1>
                        <p class="text-muted mb-0">Cập nhật thông tin danh mục: <strong><?= e($categoryData['TenDM']) ?></strong></p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="/admin/categories" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Quay lại
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= e($success) ?>
                    <div class="mt-2">
                        <small class="text-muted">Đang chuyển hướng về danh sách danh mục...</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Thông tin danh mục
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="editCategoryForm">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-tag me-1"></i>
                                        Tên danh mục <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="ten_dm" id="tenDM"
                                           placeholder="Nhập tên danh mục" required maxlength="100"
                                           value="<?= e($_POST['ten_dm'] ?? $categoryData['TenDM']) ?>">
                                    <div class="form-text">Tên danh mục sẽ hiển thị cho người dùng</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-align-left me-1"></i>
                                        Mô tả
                                    </label>
                                    <textarea class="form-control" name="mo_ta" id="moTa" rows="4"
                                              placeholder="Nhập mô tả chi tiết về danh mục"><?= e($_POST['mo_ta'] ?? $categoryData['MoTa']) ?></textarea>
                                    <div class="form-text">Mô tả chi tiết về danh mục này</div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-sort-numeric-up me-1"></i>
                                        Thứ tự hiển thị
                                    </label>
                                    <input type="number" class="form-control" name="thu_tu" id="thuTu"
                                           value="<?= e($_POST['thu_tu'] ?? $categoryData['ThuTu']) ?>" min="0" max="999">
                                    <div class="form-text">Số thứ tự hiển thị (0 = đầu tiên)</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-toggle-on me-1"></i>
                                        Trạng thái
                                    </label>
                                    <select class="form-select" name="trang_thai" id="trangThai">
                                        <option value="1" <?= ($_POST['trang_thai'] ?? $categoryData['TrangThai']) == '1' ? 'selected' : '' ?>>
                                            ✅ Hoạt động
                                        </option>
                                        <option value="0" <?= ($_POST['trang_thai'] ?? $categoryData['TrangThai']) == '0' ? 'selected' : '' ?>>
                                            ⏸️ Tạm dừng
                                        </option>
                                    </select>
                                    <div class="form-text">Trạng thái hiển thị của danh mục</div>
                                </div>

                                <!-- Category Info -->
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Thông tin danh mục
                                        </h6>
                                        <small class="text-muted">
                                            <strong>ID:</strong> #<?= $categoryData['ID'] ?><br>
                                            <strong>Ngày tạo:</strong> <?= date('d/m/Y H:i', strtotime($categoryData['NgayTao'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/admin/categories" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Hủy bỏ
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Cập nhật danh mục
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/layouts/admin/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editCategoryForm');
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const tenDM = document.getElementById('tenDM').value.trim();
        
        if (!tenDM) {
            e.preventDefault();
            alert('Vui lòng nhập tên danh mục');
            document.getElementById('tenDM').focus();
            return false;
        }
        
        if (tenDM.length > 100) {
            e.preventDefault();
            alert('Tên danh mục không được quá 100 ký tự');
            document.getElementById('tenDM').focus();
            return false;
        }
    });
});
</script>
</body>
</html>
