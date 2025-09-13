<?php
/**
 * Admin Transactions Management
 * Tro365 - Website thuê trọ
 */

// Performance optimization includes
require_once __DIR__ . '/../../../includes/performance/optimization.php';

use Tro365\Core\Auth;
use Tro365\Models\Transaction;
use Tro365\Core\Database;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();
$transaction = new Transaction();
$db = Database::getInstance();

// Require admin role
$auth->requireAdmin();

$currentUser = $auth->getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $transactionId = (int)($_POST['transaction_id'] ?? 0);

        if ($action === 'update_status' && $transactionId > 0) {
            $newStatus = $_POST['status'] ?? '';
            $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

            if (in_array($newStatus, $validStatuses)) {
                $transaction->updateStatus($transactionId, $newStatus);
                $success = "Cập nhật trạng thái giao dịch thành công!";
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
$filters = [];

if (!empty($status)) {
    $filters['status'] = $status;
}

if (!empty($search)) {
    $filters['search'] = $search;
}

// Get transactions - with caching
$cacheKey = 'admin_transactions_' . md5(serialize([$page, $limit, $filters]));
$transactions = cache_get($cacheKey);

if ($transactions === null) {
    $transactions = $transaction->getAll($page, $limit, $filters);
    // Cache for 2 minutes (transactions change frequently)
    cache_set($cacheKey, $transactions, 120);
}

// Get total count - with caching
$countCacheKey = 'admin_transactions_count_' . md5(serialize($filters));
$total = cache_get($countCacheKey);

if ($total === null) {
    $total = $transaction->count($filters);
    cache_set($countCacheKey, $total, 120);
}

$totalPages = ceil($total / $limit);

// Get status counts
$statusCounts = [
    'all' => $transaction->count([]),
    'pending' => $transaction->count(['status' => 'pending']),
    'confirmed' => $transaction->count(['status' => 'confirmed']),
    'completed' => $transaction->count(['status' => 'completed']),
    'cancelled' => $transaction->count(['status' => 'cancelled'])
];

// Get commission stats
$commissionStats = $db->selectOne(
    "SELECT
        COUNT(*) as total_commissions,
        SUM(SoTien) as total_amount,
        SUM(CASE WHEN TrangThai = 'paid' THEN SoTien ELSE 0 END) as paid_amount,
        SUM(CASE WHEN TrangThai = 'pending' THEN SoTien ELSE 0 END) as pending_amount
     FROM HoaHong"
);

$pageTitle = 'Quản lý giao dịch';
include __DIR__ . '/../../../includes/layouts/admin/header.php';
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
                        <i class="fas fa-handshake me-1"></i>
                        Quản lý giao dịch
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-handshake me-3"></i>
                            Quản lý giao dịch
                        </h1>
                        <p class="text-muted mb-0">Xem và quản lý tất cả giao dịch thuê trọ trong hệ thống</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success" onclick="exportTransactions()">
                            <i class="fas fa-download me-2"></i>Xuất báo cáo
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="refreshTransactions()">
                            <i class="fas fa-sync-alt me-2"></i>Làm mới
                        </button>
                        <button type="button" class="btn btn-primary" onclick="generateReport()">
                            <i class="fas fa-chart-line me-2"></i>Tạo báo cáo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($statusCounts['all']) ?></h3>
                                    <p class="mb-2">Tổng giao dịch</p>
                                    <small>
                                        <i class="fas fa-chart-line me-1"></i>
                                        Tất cả giao dịch
                                    </small>
                                </div>
                                <i class="fas fa-handshake card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($statusCounts['pending']) ?></h3>
                                    <p class="mb-2">Chờ xác nhận</p>
                                    <small>
                                        <i class="fas fa-clock me-1"></i>
                                        Cần xử lý ngay
                                    </small>
                                </div>
                                <i class="fas fa-hourglass-half card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($statusCounts['confirmed']) ?></h3>
                                    <p class="mb-2">Đã xác nhận</p>
                                    <small>
                                        <i class="fas fa-check-circle me-1"></i>
                                        Đã được duyệt
                                    </small>
                                </div>
                                <i class="fas fa-check-circle card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($statusCounts['completed']) ?></h3>
                                    <p class="mb-2">Hoàn thành</p>
                                    <small>
                                        <i class="fas fa-trophy me-1"></i>
                                        Giao dịch thành công
                                    </small>
                                </div>
                                <i class="fas fa-trophy card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commission Stats -->
            <div class="row mb-4">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card stats-card bg-gradient-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($commissionStats['total_amount'] ?? 0, 0, ',', '.') ?></h3>
                                    <p class="mb-2">Tổng hoa hồng</p>
                                    <small>
                                        <i class="fas fa-coins me-1"></i>
                                        VNĐ
                                    </small>
                                </div>
                                <i class="fas fa-coins card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card stats-card bg-gradient-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($commissionStats['paid_amount'] ?? 0, 0, ',', '.') ?></h3>
                                    <p class="mb-2">Đã thanh toán</p>
                                    <small>
                                        <i class="fas fa-credit-card me-1"></i>
                                        VNĐ
                                    </small>
                                </div>
                                <i class="fas fa-credit-card card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card stats-card bg-gradient-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($commissionStats['pending_amount'] ?? 0, 0, ',', '.') ?></h3>
                                    <p class="mb-2">Chờ thanh toán</p>
                                    <small>
                                        <i class="fas fa-clock me-1"></i>
                                        VNĐ
                                    </small>
                                </div>
                                <i class="fas fa-wallet card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                                <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
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

            <!-- Transactions List -->
            <div class="card admin-header-mobile admin-table-mobile">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-handshake me-2"></i>
                        Danh sách giao dịch
                        <?php if ($total > 0): ?>
                            <span class="badge bg-primary ms-2"><?= $total ?></span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($transactions)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Không có giao dịch nào</h5>
                            <p class="text-muted">
                                <?php if (!empty($search) || !empty($status)): ?>
                                    Thử thay đổi bộ lọc để xem kết quả khác
                                <?php else: ?>
                                    Chưa có giao dịch nào được tạo
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Người thuê</th>
                                        <th>Chủ nhà</th>
                                        <th>Bài đăng</th>
                                        <th>Giá thuê</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $trans): ?>
                                        <tr>
                                            <td><strong>#<?= $trans['ID'] ?></strong></td>
                                            <td>
                                                <div>
                                                    <strong><?= e($trans['TenNguoiThue']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= e($trans['TenChuNha']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= e(truncateText($trans['TenBaiDang'], 30)) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="text-success">
                                                    <?= formatCurrency($trans['GiaThue']) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php
                                                $statusLabels = [
                                                    'pending' => ['Chờ xác nhận', 'warning'],
                                                    'confirmed' => ['Đã xác nhận', 'info'],
                                                    'completed' => ['Hoàn thành', 'success'],
                                                    'cancelled' => ['Đã hủy', 'danger']
                                                ];
                                                $statusInfo = $statusLabels[$trans['TrangThai']] ?? ['Không xác định', 'secondary'];
                                                ?>
                                                <span class="badge bg-<?= $statusInfo[1] ?>"><?= $statusInfo[0] ?></span>
                                                <span class="badge bg-${statusInfo[1]}">
                                                    <i class="fas fa-${
                                                        $trans['TrangThai'] === 'completed' ? 'trophy' : (
                                                            $trans['TrangThai'] === 'confirmed' ? 'check-circle' : (
                                                                $trans['TrangThai'] === 'pending' ? 'clock' : 'times-circle'
                                                            )
                                                        )
                                                    } me-1"></i>
                                                    ${statusInfo[0]}
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i', strtotime($trans['NgayTao'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                                            onclick="showTransactionModal(<?= $trans['ID'] ?>, '<?= $trans['TrangThai'] ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info btn-sm"
                                                            onclick="viewTransactionDetails(<?= $trans['ID'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
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
                                <nav aria-label="Transactions pagination">
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
                <h5 class="modal-title">Cập nhật trạng thái giao dịch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="transaction_id" id="modalTransactionId">

                    <div class="mb-3">
                        <label class="form-label">Trạng thái mới</label>
                        <select name="status" class="form-select" id="modalStatus" required>
                            <option value="pending">Chờ xác nhận</option>
                            <option value="confirmed">Đã xác nhận</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                        <div class="form-text">
                            <strong>Lưu ý:</strong> Chọn "Hoàn thành" sẽ tự động tính hoa hồng.
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

<?php include __DIR__ . '/../../../includes/layouts/admin/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Transaction management functions
function showTransactionModal(transactionId, currentStatus) {
    document.getElementById('modalTransactionId').value = transactionId;
    document.getElementById('modalStatus').value = currentStatus;

    const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
}

function viewTransactionDetails(transactionId) {
    // Open transaction detail modal or page
    window.open(`/admin/transactions/view/${transactionId}`, '_blank');
}

function exportTransactions() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Báo cáo giao dịch - Trọ 365\n";
    csvContent += "Thời gian xuất: " + new Date().toLocaleString('vi-VN') + "\n\n";

    csvContent += "ID,Bài đăng,Người thuê,Seller,Giá,Hoa hồng,Trạng thái,Ngày tạo\n";

    // Get visible transaction data
    const transactionRows = document.querySelectorAll('tbody tr');
    transactionRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 0) {
            const id = cells[0].textContent.trim().replace('#', '');
            const post = cells[1].textContent.trim();
            const tenant = cells[2].textContent.trim();
            const seller = cells[3].textContent.trim();
            const price = cells[4].textContent.trim();
            const commission = cells[5].textContent.trim();
            const status = cells[6].textContent.trim();
            const date = cells[7].textContent.trim();

            csvContent += `"${id}","${post}","${tenant}","${seller}","${price}","${commission}","${status}","${date}"\n`;
        }
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "giao-dich-" + new Date().toISOString().split('T')[0] + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function refreshTransactions() {
    const refreshBtn = document.querySelector('[onclick="refreshTransactions()"]');
    if (refreshBtn) {
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i>Đang làm mới...';
        refreshBtn.disabled = true;
    }

    // Soft refresh only the transactions card
    const params = new URLSearchParams(window.location.search);
    fetch(`/admin/transactions?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
      .then(res => res.text())
      .then(html => {
        const temp = document.createElement('div'); temp.innerHTML = html;
        const newCard = temp.querySelector('.card.admin-header-mobile');
        const oldCard = document.querySelector('.card.admin-header-mobile');
        if (newCard && oldCard) {
            oldCard.replaceWith(newCard);
            showToast('Đã làm mới danh sách giao dịch', 'info');
        } else {
            window.location.reload();
        }
      })
      .catch(() => window.location.reload())
      .finally(() => {
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Làm mới';
            refreshBtn.disabled = false;
        }
      });
}

function generateReport() {
    // TODO: Implement detailed report generation
    // Show user-friendly notification instead of alert
    const notification = document.createElement('div');
    notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    notification.innerHTML = `
        <i class="fas fa-info-circle me-2"></i>
        <strong>Tính năng đang phát triển</strong><br>
        Báo cáo chi tiết sẽ có trong cập nhật tiếp theo. Hiện tại bạn có thể xuất dữ liệu CSV.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Bulk actions - TODO: Implement backend support for bulk operations
function bulkUpdateStatus(status) {
    const checkedBoxes = document.querySelectorAll('.transaction-checkbox:checked');
    if (checkedBoxes.length === 0) {
        // Show proper validation message (unified)
        if (window.TroToast && typeof window.TroToast.show === 'function') {
            window.TroToast.show({ message: 'Vui lòng chọn ít nhất một giao dịch', type: 'warning', duration: 3000 });
        } else {
            alert('Vui lòng chọn ít nhất một giao dịch');
        }
        return;
    }

    const statusText = {
        'pending': 'Chờ xác nhận',
        'confirmed': 'Đã xác nhận',
        'completed': 'Hoàn thành',
        'cancelled': 'Đã hủy'
    };

    if (confirm(`Cập nhật ${checkedBoxes.length} giao dịch thành "${statusText[status]}"?`)) {
        // TODO: Implement actual bulk update API call
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
        notification.innerHTML = `
            <i class="fas fa-clock me-2"></i>
            <strong>Tính năng đang phát triển</strong><br>
            Cập nhật hàng loạt sẽ có trong cập nhật tiếp theo.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
}

// Auto-refresh every 30 seconds for pending transactions
setInterval(() => {
    const pendingCount = document.querySelector('.stats-card.bg-warning h3');
    if (pendingCount && parseInt(pendingCount.textContent) > 0) {
        // Subtle refresh indicator
        const refreshIndicator = document.createElement('div');
        refreshIndicator.className = 'position-fixed top-0 end-0 m-3 alert alert-info alert-dismissible fade show';
        refreshIndicator.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i>Đang kiểm tra giao dịch mới...';
        document.body.appendChild(refreshIndicator);

        setTimeout(() => {
            refreshIndicator.remove();
        }, 2000);
    }
}, 30000);
</script>

</body>
</html>
