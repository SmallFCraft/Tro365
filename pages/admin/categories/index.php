<?php
/**
 * Admin Categories Management
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;
use Tro365\Models\Category;
use Tro365\Activity;

$auth = new Auth();
$category = new Category();

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
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $data = [
                    'TenDM' => trim($_POST['ten_dm'] ?? ''),
                    'MoTa' => trim($_POST['mo_ta'] ?? ''),
                    'ThuTu' => (int)($_POST['thu_tu'] ?? 0),
                    'TrangThai' => (int)($_POST['trang_thai'] ?? 1)
                ];
                
                $categoryId = $category->create($data);
                
                // Log activity
                try {
                    $activity = new Activity();
                    $activity->log($currentUser['ID'], 'create_category', 'Tạo danh mục: ' . $data['TenDM'], ['category_id' => $categoryId]);
                } catch (Exception $e) {
                    writeLog("Activity log error: " . $e->getMessage());
                }
                
                $success = 'Tạo danh mục thành công!';
                break;
                
            case 'update':
                $id = (int)($_POST['id'] ?? 0);
                $data = [
                    'TenDM' => trim($_POST['ten_dm'] ?? ''),
                    'MoTa' => trim($_POST['mo_ta'] ?? ''),
                    'ThuTu' => (int)($_POST['thu_tu'] ?? 0),
                    'TrangThai' => (int)($_POST['trang_thai'] ?? 1)
                ];
                
                $category->update($id, $data);
                
                // Log activity
                try {
                    $activity = new Activity();
                    $activity->log($currentUser['ID'], 'update_category', 'Cập nhật danh mục: ' . $data['TenDM'], ['category_id' => $id]);
                } catch (Exception $e) {
                    writeLog("Activity log error: " . $e->getMessage());
                }
                
                $success = 'Cập nhật danh mục thành công!';
                break;
                
            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                $categoryData = $category->getById($id);
                
                if (!$categoryData) {
                    throw new Exception('Không tìm thấy danh mục');
                }
                
                $category->delete($id);
                
                // Log activity
                try {
                    $activity = new Activity();
                    $activity->log($currentUser['ID'], 'delete_category', 'Xóa danh mục: ' . $categoryData['TenDM'], ['category_id' => $id]);
                } catch (Exception $e) {
                    writeLog("Activity log error: " . $e->getMessage());
                }
                
                $success = 'Xóa danh mục thành công!';
                break;
                
            case 'toggle_status':
                $id = (int)($_POST['id'] ?? 0);
                $categoryData = $category->getById($id);

                if (!$categoryData) {
                    throw new Exception('Không tìm thấy danh mục');
                }

                $category->toggleStatus($id);

                // Log activity
                try {
                    $activity = new Activity();
                    $newStatus = $categoryData['TrangThai'] == 1 ? 'Tắt' : 'Bật';
                    $activity->log($currentUser['ID'], 'toggle_category_status', $newStatus . ' danh mục: ' . $categoryData['TenDM'], ['category_id' => $id]);
                } catch (Exception $e) {
                    writeLog("Activity log error: " . $e->getMessage());
                }

                $newStatus = $categoryData['TrangThai'] == 1 ? 'Tắt' : 'Bật';
                $success = $newStatus . ' danh mục thành công!';
                break;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;

// Build filters
$filters = [];
if (!empty($search)) {
    $filters['search'] = $search;
}
if ($status !== '') {
    $filters['status'] = (int)$status;
}

// Get all categories with post count (for admin)
$categories = $category->getAllCategoriesWithPostCount();

// Filter categories if needed
if (!empty($filters)) {
    $categories = array_filter($categories, function($cat) use ($filters) {
        if (!empty($filters['search'])) {
            if (stripos($cat['TenDM'], $filters['search']) === false) {
                return false;
            }
        }
        if (isset($filters['status'])) {
            if ($cat['TrangThai'] != $filters['status']) {
                return false;
            }
        }
        return true;
    });
}

// Pagination
$total = count($categories);
$totalPages = ceil($total / $limit);
$offset = ($page - 1) * $limit;
$categories = array_slice($categories, $offset, $limit);

$pageTitle = 'Quản lý danh mục';
$pageDescription = 'Quản lý danh mục bài đăng';

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
                    <li class="breadcrumb-item active">
                        <i class="fas fa-tags me-1"></i>
                        Quản lý danh mục
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-tags me-3"></i>
                            Quản lý danh mục
                        </h1>
                        <p class="text-muted mb-0">Quản lý các danh mục bài đăng trong hệ thống</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="refreshPage()">
                            <i class="fas fa-sync-alt me-2"></i>
                            Làm mới
                        </button>
                        <a href="/admin/categories/create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Thêm danh mục
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
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        Bộ lọc tìm kiếm
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-4">
                        <div class="col-md-5">
                            <label class="form-label">
                                <i class="fas fa-search me-1"></i>
                                Tìm kiếm theo tên
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" name="search"
                                       value="<?= e($search) ?>" placeholder="Nhập tên danh mục...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>
                                Trạng thái
                            </label>
                            <select class="form-select" name="status">
                                <option value="">🔍 Tất cả trạng thái</option>
                                <option value="1" <?= $status === '1' ? 'selected' : '' ?>>✅ Hoạt động</option>
                                <option value="0" <?= $status === '0' ? 'selected' : '' ?>>⏸️ Tạm dừng</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-search me-2"></i>Tìm kiếm
                            </button>
                            <a href="/admin/categories" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Danh sách danh mục
                        </h5>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-primary fs-6">
                                <?= $total ?> danh mục
                            </span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="exportData()">
                                    <i class="fas fa-download me-1"></i>Xuất
                                </button>
                                <button type="button" class="btn btn-outline-success" onclick="importData()">
                                    <i class="fas fa-upload me-1"></i>Nhập
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($categories)): ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-tags fa-4x text-muted opacity-50"></i>
                            </div>
                            <h4 class="text-muted mb-3">Chưa có danh mục nào</h4>
                            <p class="text-muted mb-4">Hãy tạo danh mục đầu tiên để bắt đầu phân loại bài đăng</p>
                            <a href="/admin/categories/create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Tạo danh mục đầu tiên
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="5%">
                                            <i class="fas fa-hashtag me-1"></i>ID
                                        </th>
                                        <th width="25%">
                                            <i class="fas fa-tag me-1"></i>Tên danh mục
                                        </th>
                                        <th width="30%">
                                            <i class="fas fa-align-left me-1"></i>Mô tả
                                        </th>
                                        <th width="8%">
                                            <i class="fas fa-sort-numeric-up me-1"></i>Thứ tự
                                        </th>
                                        <th width="10%">
                                            <i class="fas fa-home me-1"></i>Bài đăng
                                        </th>
                                        <th width="10%">
                                            <i class="fas fa-toggle-on me-1"></i>Trạng thái
                                        </th>
                                        <th width="12%">
                                            <i class="fas fa-calendar me-1"></i>Ngày tạo
                                        </th>
                                        <th width="15%" class="text-center">
                                            <i class="fas fa-cogs me-1"></i>Thao tác
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold text-primary">#<?= $cat['ID'] ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="category-icon me-3">
                                                        <i class="fas fa-tag text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= e($cat['TenDM']) ?></div>
                                                        <small class="text-muted">ID: <?= $cat['ID'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="description-text">
                                                    <?php if (!empty($cat['MoTa'])): ?>
                                                        <?= e(substr($cat['MoTa'], 0, 60)) ?>
                                                        <?= strlen($cat['MoTa']) > 60 ? '...' : '' ?>
                                                    <?php else: ?>
                                                        <em class="text-muted">Chưa có mô tả</em>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary fs-6"><?= $cat['ThuTu'] ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info fs-6">
                                                    <i class="fas fa-home me-1"></i>
                                                    <?= $cat['SoBaiDang'] ?? 0 ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($cat['TrangThai'] == 1): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Hoạt động
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-pause me-1"></i>Tạm dừng
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?= date('d/m/Y', strtotime($cat['NgayTao'])) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= date('H:i', strtotime($cat['NgayTao'])) ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button"
                                                            class="btn btn-outline-primary btn-edit"
                                                            data-id="<?= $cat['ID'] ?>"
                                                            data-ten="<?= e($cat['TenDM']) ?>"
                                                            data-mota="<?= e($cat['MoTa'] ?? '') ?>"
                                                            data-thutu="<?= $cat['ThuTu'] ?>"
                                                            data-trangthai="<?= $cat['TrangThai'] ?>"
                                                            title="Chỉnh sửa danh mục"
                                                            data-bs-toggle="tooltip">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <form method="POST" class="d-inline"
                                                          onsubmit="return confirm('Bạn có chắc muốn <?= $cat['TrangThai'] == 1 ? 'TẮT' : 'BẬT' ?> danh mục \"<?= e($cat['TenDM']) ?>\"?\n\n<?= $cat['TrangThai'] == 1 ? 'Danh mục sẽ không hiển thị cho người dùng.' : 'Danh mục sẽ hiển thị trở lại cho người dùng.' ?>')">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="id" value="<?= $cat['ID'] ?>">
                                                        <button type="submit"
                                                                class="btn btn-outline-<?= $cat['TrangThai'] == 1 ? 'warning' : 'success' ?> btn-toggle-status"
                                                                title="<?= $cat['TrangThai'] == 1 ? 'Click để TẮT danh mục' : 'Click để BẬT danh mục' ?>"
                                                                data-bs-toggle="tooltip"
                                                                data-status="<?= $cat['TrangThai'] ?>"
                                                                data-name="<?= e($cat['TenDM']) ?>">
                                                            <i class="fas fa-<?= $cat['TrangThai'] == 1 ? 'eye-slash' : 'eye' ?>"></i>
                                                            <?= $cat['TrangThai'] == 1 ? ' Tắt' : ' Bật' ?>
                                                        </button>
                                                    </form>

                                                    <?php if (($cat['SoBaiDang'] ?? 0) == 0): ?>
                                                        <form method="POST" class="d-inline"
                                                              onsubmit="return confirm('⚠️ Bạn có chắc muốn xóa danh mục này?\n\nHành động này không thể hoàn tác!')">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="id" value="<?= $cat['ID'] ?>">
                                                            <button type="submit"
                                                                    class="btn btn-outline-danger"
                                                                    title="Xóa danh mục"
                                                                    data-bs-toggle="tooltip">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button type="button"
                                                                class="btn btn-outline-secondary"
                                                                title="Không thể xóa danh mục có bài đăng"
                                                                data-bs-toggle="tooltip"
                                                                disabled>
                                                            <i class="fas fa-lock"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Toggle status button styling */
.btn-toggle-status {
    transition: all 0.3s ease;
    font-size: 0.875rem;
}

.btn-toggle-status:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-toggle-status[data-status="1"]:hover {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000;
}

.btn-toggle-status[data-status="0"]:hover {
    background-color: #198754;
    border-color: #198754;
    color: #fff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Edit button handlers
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            // Redirect to edit page
            window.location.href = '/admin/categories/edit?id=' + id;
        });
    });


});

// Utility functions
function refreshPage() {
    window.location.reload();
}

function exportData() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "ID,Tên danh mục,Mô tả,Thứ tự,Số bài đăng,Trạng thái,Ngày tạo\n";

    // Get table data
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            const id = cells[0].textContent.trim().replace('#', '');
            const name = cells[1].querySelector('.fw-bold').textContent.trim();
            const desc = cells[2].textContent.trim();
            const order = cells[3].textContent.trim();
            const posts = cells[4].textContent.trim();
            const status = cells[5].textContent.trim();
            const date = cells[6].textContent.trim();

            csvContent += `"${id}","${name}","${desc}","${order}","${posts}","${status}","${date}"\n`;
        }
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "danh-muc-" + new Date().toISOString().split('T')[0] + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function importData() {
    alert('Chức năng nhập dữ liệu sẽ được phát triển trong phiên bản tiếp theo.');
}
</script>

<?php include __DIR__ . '/../../../includes/layouts/admin/footer.php'; ?>
