<?php
/**
 * Admin User Create
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;
use Tro365\Core\Database;
use Tro365\Activity;

$auth = new Auth();
$db = Database::getInstance();

// Require admin role
$auth->requireAdmin();

$currentUser = $auth->getCurrentUser();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }
        
        $data = [
            'TenDN' => trim($_POST['ten_dn'] ?? ''),
            'Email' => trim($_POST['email'] ?? ''),
            'MatKhau' => $_POST['mat_khau'] ?? '',
            'HoTen' => trim($_POST['ho_ten'] ?? ''),
            'NgaySinh' => $_POST['ngay_sinh'] ?: null,
            'GioiTinh' => $_POST['gioi_tinh'] ?: null,
            'CCCD' => trim($_POST['cccd'] ?? '') ?: null,
            'SDT' => trim($_POST['sdt'] ?? '') ?: null,
            'DiaChi' => trim($_POST['dia_chi'] ?? '') ?: null,
            'TinhThanhID' => $_POST['tinh_thanh_id'] ?? null,
            'QuanHuyenID' => $_POST['quan_huyen_id'] ?? null,
            'XaPhuongID' => $_POST['xa_phuong_id'] ?? null,
            'VaiTroID' => (int)($_POST['vai_tro_id'] ?? 1),
            'TrangThai' => (int)($_POST['trang_thai'] ?? 1)
        ];
        
        // Basic validation using ValidationHelper
        \Tro365\Helpers\ValidationHelper::validateRequired($data, ['TenDN', 'Email', 'MatKhau', 'HoTen']);

        // Additional validation
        if (strlen($data['TenDN']) < 3 || strlen($data['TenDN']) > 30) {
            throw new Exception('Tên đăng nhập phải có từ 3-30 ký tự');
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['TenDN'])) {
            throw new Exception('Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới');
        }

        if (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email không hợp lệ');
        }

        if (strlen($data['MatKhau']) < 6) {
            throw new Exception('Mật khẩu phải có ít nhất 6 ký tự');
        }

        if (strlen($data['HoTen']) < 2) {
            throw new Exception('Họ tên phải có ít nhất 2 ký tự');
        }
        
        // Check if username exists
        $existingUser = $db->selectOne("SELECT ID FROM KhachHang WHERE TenDN = :username", ['username' => $data['TenDN']]);
        if ($existingUser) {
            throw new Exception('Tên đăng nhập đã tồn tại');
        }
        
        // Check if email exists
        $existingEmail = $db->selectOne("SELECT ID FROM KhachHang WHERE Email = :email", ['email' => $data['Email']]);
        if ($existingEmail) {
            throw new Exception('Email đã tồn tại');
        }
        
        // Hash password
        $data['MatKhau'] = password_hash($data['MatKhau'], PASSWORD_DEFAULT);
        
        // Insert user
        $userId = $db->insert('KhachHang', $data);
        
        // Log activity
        try {
            $activity = new Activity();
            $activity->log($currentUser['ID'], 'create_user', 'Tạo người dùng: ' . $data['HoTen'], ['user_id' => $userId]);
        } catch (Exception $e) {
            writeLog("Activity log error: " . $e->getMessage());
        }
        
        $success = 'Tạo người dùng thành công!';
        
        // Redirect to user info after 2 seconds
        header("refresh:2;url=/admin/users/info?id=" . $userId);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get roles for dropdown
$roles = $db->select("SELECT * FROM VaiTro WHERE TrangThai = 1 ORDER BY CapDo ASC");

$pageTitle = 'Tạo người dùng mới';
$pageDescription = 'Thêm người dùng mới vào hệ thống';

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
                        <a href="/admin/users">
                            <i class="fas fa-users me-1"></i>
                            Quản lý người dùng
                        </a>
                    </li>
                    <li class="breadcrumb-item active">
                        <i class="fas fa-user-plus me-1"></i>
                        Tạo người dùng
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-user-plus me-3"></i>
                            Tạo người dùng mới
                        </h1>
                        <p class="text-muted mb-0">Thêm người dùng mới vào hệ thống</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="/admin/users" class="btn btn-outline-secondary">
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
                        <small class="text-muted">Đang chuyển hướng đến trang thông tin người dùng...</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Create Form -->
            <form method="POST" id="createUserForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="row">
                    <!-- Basic Info -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user me-2"></i>
                                    Thông tin cơ bản
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Tên đăng nhập <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" name="ten_dn"
                                                   placeholder="Nhập tên đăng nhập" required autocomplete="username"
                                                   value="<?= e($_POST['ten_dn'] ?? '') ?>">
                                            <div class="form-text">3-50 ký tự, chỉ chữ cái, số và dấu gạch dưới</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Email <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" class="form-control" name="email"
                                                   placeholder="Nhập email" required autocomplete="email"
                                                   value="<?= e($_POST['email'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Mật khẩu <span class="text-danger">*</span>
                                            </label>
                                            <input type="password" class="form-control" name="mat_khau"
                                                   placeholder="Nhập mật khẩu" required autocomplete="new-password">
                                            <div class="form-text">Ít nhất 6 ký tự</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Họ tên <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" name="ho_ten" 
                                                   placeholder="Nhập họ tên đầy đủ" required
                                                   value="<?= e($_POST['ho_ten'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Ngày sinh</label>
                                            <input type="date" class="form-control" name="ngay_sinh"
                                                   value="<?= e($_POST['ngay_sinh'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Giới tính</label>
                                            <select class="form-select" name="gioi_tinh">
                                                <option value="">Chọn giới tính</option>
                                                <option value="Nam" <?= ($_POST['gioi_tinh'] ?? '') === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                                <option value="Nữ" <?= ($_POST['gioi_tinh'] ?? '') === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                                <option value="Khác" <?= ($_POST['gioi_tinh'] ?? '') === 'Khác' ? 'selected' : '' ?>>Khác</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">CCCD</label>
                                            <input type="text" class="form-control" name="cccd" 
                                                   placeholder="Số CCCD"
                                                   value="<?= e($_POST['cccd'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Số điện thoại</label>
                                            <input type="tel" class="form-control" name="sdt" 
                                                   placeholder="Số điện thoại"
                                                   value="<?= e($_POST['sdt'] ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Địa chỉ</label>
                                            <input type="text" class="form-control" name="dia_chi" 
                                                   placeholder="Địa chỉ chi tiết"
                                                   value="<?= e($_POST['dia_chi'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role & Status -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-cog me-2"></i>
                                    Vai trò & Trạng thái
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Vai trò</label>
                                    <select class="form-select" name="vai_tro_id">
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= $role['ID'] ?>" 
                                                    <?= ($_POST['vai_tro_id'] ?? '1') == $role['ID'] ? 'selected' : '' ?>>
                                                <?= e($role['TenVT']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Trạng thái</label>
                                    <select class="form-select" name="trang_thai">
                                        <option value="1" <?= ($_POST['trang_thai'] ?? '1') == '1' ? 'selected' : '' ?>>
                                            ✅ Hoạt động
                                        </option>
                                        <option value="0" <?= ($_POST['trang_thai'] ?? '1') == '0' ? 'selected' : '' ?>>
                                            ⏸️ Tạm khóa
                                        </option>
                                        <option value="2" <?= ($_POST['trang_thai'] ?? '1') == '2' ? 'selected' : '' ?>>
                                            🚫 Bị cấm
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Tạo người dùng
                            </button>
                            <a href="/admin/users" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Hủy bỏ
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/layouts/admin/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createUserForm');
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const tenDN = document.querySelector('input[name="ten_dn"]').value.trim();
        const email = document.querySelector('input[name="email"]').value.trim();
        const matKhau = document.querySelector('input[name="mat_khau"]').value;
        const hoTen = document.querySelector('input[name="ho_ten"]').value.trim();
        
        if (!tenDN || tenDN.length < 3 || tenDN.length > 50) {
            e.preventDefault();
            alert('Tên đăng nhập phải từ 3-50 ký tự');
            return false;
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(tenDN)) {
            e.preventDefault();
            alert('Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới');
            return false;
        }
        
        if (!email || !email.includes('@')) {
            e.preventDefault();
            alert('Vui lòng nhập email hợp lệ');
            return false;
        }
        
        if (!matKhau || matKhau.length < 6) {
            e.preventDefault();
            alert('Mật khẩu phải có ít nhất 6 ký tự');
            return false;
        }
        
        if (!hoTen) {
            e.preventDefault();
            alert('Vui lòng nhập họ tên');
            return false;
        }
    });
});
</script>
</body>
</html>
