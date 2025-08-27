<?php
/**
 * Seller Contacts Management
 * Tro365 - Website thuê trọ
 */

// Load autoloader and configuration
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions/helpers.php';
require_once __DIR__ . '/../../includes/functions/auth.php';
require_once __DIR__ . '/../../includes/functions/validation.php';

use Tro365\Core\Auth;
use Tro365\Models\Contact;

$auth = new Auth();
$contact = new Contact();

// Require seller role
$auth->requireSeller();

$currentUser = $auth->getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $contactId = (int)($_POST['contact_id'] ?? 0);
        
        if ($action === 'update_status' && $contactId > 0) {
            $newStatus = $_POST['status'] ?? '';
            $validStatuses = ['pending', 'contacted', 'interested', 'deal', 'cancelled'];
            
            if (in_array($newStatus, $validStatuses)) {
                $contact->updateStatus($contactId, $newStatus, $currentUser['ID']);
                
                // If status is 'deal', create transaction
                if ($newStatus === 'deal') {
                    $contactData = $contact->getById($contactId);
                    if ($contactData) {
                        $transactionData = [
                            'LienHeID' => $contactId,
                            'BaiDangID' => $contactData['BaiDangID'],
                            'NguoiThueID' => $contactData['NguoiLienHeID'],
                            'ChuNhaID' => $contactData['ChuNhaID'],
                            'GiaThue' => $contactData['GiaBaiDang'],
                            'GhiChu' => 'Tạo từ liên hệ thành công'
                        ];
                        
                        $transaction->create($transactionData);
                    }
                }
                
                $success = "Cập nhật trạng thái thành công!";
            } else {
                $error = "Trạng thái không hợp lệ!";
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;

// Build filters
$filters = [
    'landlord_id' => $currentUser['ID']
];

if (!empty($status)) {
    $filters['status'] = $status;
}

if (!empty($search)) {
    $filters['search'] = $search;
}

// Get contacts
$contacts = $contact->getByUser($currentUser['ID'], 'received', $page, $limit);
$total = $contact->count($filters);
$totalPages = ceil($total / $limit);

// Get status counts
$statusCounts = [
    'all' => $contact->count(['landlord_id' => $currentUser['ID']]),
    'pending' => $contact->count(['landlord_id' => $currentUser['ID'], 'status' => 'pending']),
    'contacted' => $contact->count(['landlord_id' => $currentUser['ID'], 'status' => 'contacted']),
    'interested' => $contact->count(['landlord_id' => $currentUser['ID'], 'status' => 'interested']),
    'deal' => $contact->count(['landlord_id' => $currentUser['ID'], 'status' => 'deal']),
    'cancelled' => $contact->count(['landlord_id' => $currentUser['ID'], 'status' => 'cancelled'])
];

$pageTitle = 'Quản lý liên hệ';
include __DIR__ . '/../../includes/layouts/client/header.php';
?>

<!-- Additional CSS for Seller Contacts -->
<link href="/assets/css/seller/seller-main.css" rel="stylesheet">
<link href="/assets/css/seller/seller-contacts.css" rel="stylesheet">

<div class="seller-contacts-container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="/seller">Dashboard</a></li>
            <li class="breadcrumb-item active">Quản lý liên hệ</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="seller-contacts-header seller-fade-in">
        <h2 class="seller-contacts-title">
            <i class="fas fa-envelope"></i>
            Quản lý liên hệ
        </h2>
        <p class="seller-contacts-subtitle">
            Xem và quản lý các liên hệ từ khách hàng
        </p>
    </div>

            <!-- Alerts -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <h4 class="text-primary mb-1"><?= $statusCounts['all'] ?></h4>
                            <small class="text-muted">Tổng liên hệ</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <h4 class="text-warning mb-1"><?= $statusCounts['pending'] ?></h4>
                            <small class="text-muted">Chờ xử lý</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <h4 class="text-info mb-1"><?= $statusCounts['contacted'] ?></h4>
                            <small class="text-muted">Đã liên hệ</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <h4 class="text-success mb-1"><?= $statusCounts['interested'] ?></h4>
                            <small class="text-muted">Quan tâm</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <h4 class="text-primary mb-1"><?= $statusCounts['deal'] ?></h4>
                            <small class="text-muted">Thành công</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                    <div class="card text-center">
                        <div class="card-body py-3">
                            <h4 class="text-danger mb-1"><?= $statusCounts['cancelled'] ?></h4>
                            <small class="text-muted">Đã hủy</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                <option value="contacted" <?= $status === 'contacted' ? 'selected' : '' ?>>Đã liên hệ</option>
                                <option value="interested" <?= $status === 'interested' ? 'selected' : '' ?>>Quan tâm</option>
                                <option value="deal" <?= $status === 'deal' ? 'selected' : '' ?>>Thành công</option>
                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Tìm theo tên khách hàng, bài đăng..." 
                                   value="<?= e($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Lọc
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Contacts List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope me-2"></i>
                        Danh sách liên hệ
                        <?php if ($total > 0): ?>
                            <span class="badge bg-primary ms-2"><?= $total ?></span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($contacts)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-envelope-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Không có liên hệ nào</h5>
                            <p class="text-muted">
                                <?php if (!empty($search) || !empty($status)): ?>
                                    Thử thay đổi bộ lọc để xem kết quả khác
                                <?php else: ?>
                                    Chưa có khách hàng nào liên hệ về bài đăng của bạn
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Khách hàng</th>
                                        <th>Bài đăng</th>
                                        <th>Tin nhắn</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contacts as $contactItem): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?= e($contactItem['HoTen']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-phone me-1"></i><?= e($contactItem['SDT']) ?>
                                                        <?php if (!empty($contactItem['Email'])): ?>
                                                            <br><i class="fas fa-envelope me-1"></i><?= e($contactItem['Email']) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= e(truncateText($contactItem['TenBaiDang'] ?? 'Bài đăng #' . $contactItem['BaiDangID'], 30)) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-money-bill-wave me-1"></i>
                                                        <?= number_format($contactItem['GiaBaiDang'] ?? 0, 0, ',', '.') ?> VNĐ/tháng
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($contactItem['TinNhan'])): ?>
                                                    <div class="text-truncate" style="max-width: 200px;" 
                                                         title="<?= e($contactItem['TinNhan']) ?>">
                                                        <?= e(truncateText($contactItem['TinNhan'], 50)) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Không có tin nhắn</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusLabels = [
                                                    'pending' => ['Chờ xử lý', 'warning'],
                                                    'contacted' => ['Đã liên hệ', 'info'],
                                                    'interested' => ['Quan tâm', 'success'],
                                                    'deal' => ['Thành công', 'primary'],
                                                    'cancelled' => ['Đã hủy', 'danger']
                                                ];
                                                $statusInfo = $statusLabels[$contactItem['TrangThai']] ?? ['Không xác định', 'secondary'];
                                                ?>
                                                <span class="badge bg-<?= $statusInfo[1] ?>"><?= $statusInfo[0] ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i', strtotime($contactItem['NgayTao'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                                            onclick="showContactModal(<?= $contactItem['ID'] ?>, '<?= e($contactItem['HoTen']) ?>', '<?= $contactItem['TrangThai'] ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="tel:<?= e($contactItem['SDT']) ?>" 
                                                       class="btn btn-outline-success btn-sm" 
                                                       title="Gọi điện">
                                                        <i class="fas fa-phone"></i>
                                                    </a>
                                                    <?php if (!empty($contactItem['Email'])): ?>
                                                        <a href="mailto:<?= e($contactItem['Email']) ?>" 
                                                           class="btn btn-outline-info btn-sm" 
                                                           title="Gửi email">
                                                            <i class="fas fa-envelope"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="card-footer">
                                <nav aria-label="Contacts pagination">
                                    <ul class="pagination pagination-sm justify-content-center mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cập nhật trạng thái liên hệ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="contact_id" id="modalContactId">
                    
                    <div class="mb-3">
                        <label class="form-label">Khách hàng</label>
                        <input type="text" class="form-control" id="modalCustomerName" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Trạng thái mới</label>
                        <select name="status" class="form-select" id="modalStatus" required>
                            <option value="pending">Chờ xử lý</option>
                            <option value="contacted">Đã liên hệ</option>
                            <option value="interested">Quan tâm</option>
                            <option value="deal">Thành công</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                        <div class="form-text">
                            <strong>Lưu ý:</strong> Chọn "Thành công" sẽ tự động tạo giao dịch mới.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showContactModal(contactId, customerName, currentStatus) {
        document.getElementById('modalContactId').value = contactId;
        document.getElementById('modalCustomerName').value = customerName;
        document.getElementById('modalStatus').value = currentStatus;
        
        const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
        modal.show();
    }
</script>
</body>
</html>
