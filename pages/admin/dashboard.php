<?php
/**
 * Admin Dashboard
 * Tro365 - Website thuê trọ
 */

// Load required files first
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions/auth.php';

use Tro365\Core\Auth;
use Tro365\Models\Post;
use Tro365\Core\Database;

// echo "<!-- Debug: Starting auth check -->";

// Require moderator role or higher - show 403 for unauthenticated users
requireModeratorStrict();

echo "<!-- Debug: Auth check passed -->";

try {
    $auth = new Auth();
    echo "<!-- Debug: Auth object created -->";

    $post = new Post();
    echo "<!-- Debug: Post object created -->";

    $db = Database::getInstance();
    echo "<!-- Debug: Database object created -->";
} catch (Exception $e) {
    echo "<!-- Debug Error: " . $e->getMessage() . " -->";
    die("Error creating objects: " . $e->getMessage());
}

// Force refresh session to get latest role
$auth->updateSession();

// Require admin role (dashboard only for admin)
$auth->requireAdmin();

$currentUser = $auth->getCurrentUser();

// Get statistics with error handling
try {
    $stats = [
        'total_posts' => $post->count([]),
        'pending_posts' => $post->count(['status' => POST_STATUS_PENDING]),
        'approved_posts' => $post->count(['status' => POST_STATUS_APPROVED]),
        'rejected_posts' => $post->count(['status' => POST_STATUS_REJECTED]),
        'total_users' => $db->count('KhachHang', '1=1'),
        'total_sellers' => $db->count('KhachHang', 'VaiTroID = :role', ['role' => ROLE_SELLER]),
        'pending_sellers' => $db->count('KhachHang', 'VaiTroID = :role AND TrangThai = :status', ['role' => ROLE_SELLER, 'status' => 0])
    ];
} catch (Exception $e) {
    // Fallback stats if database error
    $stats = [
        'total_posts' => 0,
        'pending_posts' => 0,
        'approved_posts' => 0,
        'rejected_posts' => 0,
        'total_users' => 0,
        'total_sellers' => 0,
        'pending_sellers' => 0
    ];
    error_log("Dashboard stats error: " . $e->getMessage());
}

// Get recent posts needing approval with error handling
try {
    $pendingPosts = $post->getAll(1, 10, ['status' => POST_STATUS_PENDING]);
} catch (Exception $e) {
    $pendingPosts = [];
    error_log("Dashboard pending posts error: " . $e->getMessage());
}

// Get recent users with error handling
try {
    $recentUsers = $db->select(
        "SELECT * FROM KhachHang ORDER BY NgayTao DESC LIMIT 10"
    );
} catch (Exception $e) {
    $recentUsers = [];
    error_log("Dashboard recent users error: " . $e->getMessage());
}

// Get total views with error handling
try {
    $viewsResult = $db->selectOne("SELECT SUM(LuotXem) as total_views FROM BaiDang");
    $stats['total_views'] = (int)($viewsResult['total_views'] ?? 0);
} catch (Exception $e) {
    $stats['total_views'] = 0;
    error_log("Dashboard total views error: " . $e->getMessage());
}
?>
<?php include __DIR__ . '/../../includes/layouts/admin/header.php'; ?>

<!-- Debug: Header included -->

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="admin-sidebar">
                <?php include __DIR__ . '/../../includes/layouts/admin/sidebar.php'; ?>
            </div>
        </div>

        <!-- Debug: After sidebar column -->

        <!-- Main content -->
        <div class="col-md-9 col-lg-10 main-content">
            <!-- Debug: Main content started -->

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
                        <i class="fas fa-tachometer-alt me-1"></i>
                        Tổng quan hệ thống
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-tachometer-alt me-3"></i>
                            Dashboard Admin
                            <small id="liveTime" class="text-muted ms-2"></small>
                        </h1>
                        <p class="text-muted mb-0">
                            Chào mừng <strong><?= e($currentUser['HoTen']) ?></strong>!
                            Quản lý và giám sát hệ thống <?= getWebsiteName() ?>.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success" onclick="exportDashboard()">
                            <i class="fas fa-download me-2"></i>Xuất báo cáo
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="refreshDashboard()">
                            <i class="fas fa-sync-alt me-2"></i>Làm mới
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['total_posts']) ?></h3>
                                    <p class="mb-2">Tổng bài đăng</p>
                                    <small>
                                        <i class="fas fa-chart-line me-1"></i>
                                        <?= $stats['approved_posts'] ?> đã duyệt
                                    </small>
                                </div>
                                <i class="fas fa-home card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['pending_posts']) ?></h3>
                                    <p class="mb-2">Chờ duyệt</p>
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
                    <div class="card stats-card bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['total_users']) ?></h3>
                                    <p class="mb-2">Người dùng</p>
                                    <small>
                                        <i class="fas fa-user-tie me-1"></i>
                                        <?= $stats['total_sellers'] ?> seller
                                    </small>
                                </div>
                                <i class="fas fa-users card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['total_views']) ?></h3>
                                    <p class="mb-2">Lượt xem</p>
                                    <small>
                                        <i class="fas fa-eye me-1"></i>
                                        Tất cả bài đăng
                                    </small>
                                </div>
                                <i class="fas fa-chart-bar card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Thao tác nhanh
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="/admin/posts" class="text-decoration-none">
                                <div class="card quick-action h-100 border-0 shadow-sm">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-clipboard-check fa-3x text-warning"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">Duyệt bài đăng</h6>
                                        <div class="mt-2">
                                            <span class="badge bg-warning fs-6">
                                                <?= $stats['pending_posts'] ?> bài chờ duyệt
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="/admin/users" class="text-decoration-none">
                                <div class="card quick-action h-100 border-0 shadow-sm">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-users fa-3x text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">Quản lý người dùng</h6>
                                        <div class="mt-2">
                                            <span class="badge bg-primary fs-6">
                                                <?= $stats['total_users'] ?> người dùng
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="/admin/sellers" class="text-decoration-none">
                                <div class="card quick-action h-100 border-0 shadow-sm">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-store fa-3x text-success"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">Quản lý seller</h6>
                                        <div class="mt-2">
                                            <span class="badge bg-success fs-6">
                                                <?= $stats['total_sellers'] ?> seller
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="/admin/statistics" class="text-decoration-none">
                                <div class="card quick-action h-100 border-0 shadow-sm">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-chart-line fa-3x text-info"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark">Thống kê</h6>
                                        <div class="mt-2">
                                            <span class="badge bg-info fs-6">
                                                Báo cáo chi tiết
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Row -->
            <div class="row">
                <!-- Pending Posts -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    Bài đăng chờ duyệt
                                </h5>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-warning fs-6">
                                        <?= count($pendingPosts) ?> bài
                                    </span>
                                    <a href="/admin/posts?status=0" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i>
                                        Xem tất cả
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($pendingPosts)): ?>
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="fas fa-check-circle fa-3x text-success opacity-50"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">Tuyệt vời!</h5>
                                    <p class="text-muted mb-0">Không có bài đăng nào chờ duyệt</p>
                                </div>
                            <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($pendingPosts as $pendingPost): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <a href="/post/<?= $pendingPost['ID'] ?>" 
                                                               class="text-decoration-none"
                                                               target="_blank">
                                                                <?= e(truncateText($pendingPost['TieuDe'], 60)) ?>
                                                            </a>
                                                        </h6>
                                                        <p class="mb-1 text-muted small">
                                                            Người đăng: <?= e($pendingPost['NguoiDang']) ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            <?= timeAgo($pendingPost['NgayTao']) ?>
                                                        </small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="text-primary fw-bold">
                                                            <?= formatCurrency($pendingPost['Gia']) ?>
                                                        </span>
                                                        <br>
                                                        <div class="btn-group btn-group-sm mt-1">
                                                            <form method="POST" action="/admin/posts" class="d-inline">
                                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                                <input type="hidden" name="action" value="approve">
                                                                <input type="hidden" name="post_id" value="<?= $pendingPost['ID'] ?>">
                                                                <button type="submit" class="btn btn-success btn-sm"
                                                                        title="Duyệt bài đăng"
                                                                        onclick="return confirm('Bạn có chắc muốn duyệt bài đăng này?')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" action="/admin/posts" class="d-inline">
                                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                                <input type="hidden" name="action" value="reject">
                                                                <input type="hidden" name="post_id" value="<?= $pendingPost['ID'] ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm"
                                                                        title="Từ chối bài đăng"
                                                                        onclick="return confirm('Bạn có chắc muốn từ chối bài đăng này?')">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <!-- Recent Users -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Người dùng mới
                                </h5>
                                <span class="badge bg-primary">
                                    <?= count($recentUsers) ?> người
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recentUsers)): ?>
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="fas fa-users fa-3x text-muted opacity-50"></i>
                                    </div>
                                    <p class="text-muted mb-0">Chưa có người dùng mới</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($recentUsers, 0, 5) as $user): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle me-3">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1 fw-bold"><?= e($user['HoTen']) ?></h6>
                                                        <p class="mb-1 small text-muted">
                                                            <i class="fas fa-envelope me-1"></i>
                                                            <?= e($user['Email']) ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?= timeAgo($user['NgayTao']) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <?php
                                                    $roleClass = '';
                                                    $roleText = '';
                                                    switch ($user['VaiTroID']) {
                                                        case ROLE_ADMIN:
                                                            $roleClass = 'bg-danger';
                                                            $roleText = 'Admin';
                                                            break;
                                                        case ROLE_MODERATOR:
                                                            $roleClass = 'bg-warning';
                                                            $roleText = 'Mod';
                                                            break;
                                                        case ROLE_SELLER:
                                                            $roleClass = 'bg-success';
                                                            $roleText = 'Seller';
                                                            break;
                                                        default:
                                                            $roleClass = 'bg-primary';
                                                            $roleText = 'User';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $roleClass ?> fs-6">
                                                        <?= $roleText ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>

<?php include __DIR__ . '/../../includes/layouts/admin/footer.php'; ?>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.9rem;
}

.quick-action {
    transition: all 0.3s ease;
    border-radius: 15px;
}

.quick-action:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.quick-action .card-body {
    border-radius: 15px;
}

.list-group-item {
    border: none;
    border-bottom: 1px solid #f1f5f9;
    padding: 1rem;
    transition: all 0.3s ease;
}

.list-group-item:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
    transform: translateX(5px);
}

.list-group-item:last-child {
    border-bottom: none;
}

@media (max-width: 768px) {
    .stats-card h3 {
        font-size: 2rem;
    }

    .quick-action .card-body {
        padding: 1.5rem;
    }

    .avatar-circle {
        width: 35px;
        height: 35px;
        font-size: 0.8rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update live time
    function updateLiveTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('vi-VN');
        const liveTimeElement = document.getElementById('liveTime');
        if (liveTimeElement) {
            liveTimeElement.innerHTML = `<i class="fas fa-clock me-1"></i>Cập nhật: ${timeString}`;
        }
    }

    // Update time every second
    setInterval(updateLiveTime, 1000);
    updateLiveTime();

    // Animate numbers on page load
    function animateNumbers() {
        const numbers = document.querySelectorAll('.stats-card h3');
        numbers.forEach(num => {
            const finalValue = parseInt(num.textContent.replace(/[^\d]/g, ''));
            if (finalValue > 0) {
                let currentValue = 0;
                const increment = finalValue / 30;
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    num.textContent = Math.floor(currentValue).toLocaleString('vi-VN');
                }, 50);
            }
        });
    }

    // Initialize animations
    setTimeout(animateNumbers, 500);
});

// Dashboard functions
function refreshDashboard() {
    const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
    if (refreshBtn) {
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i>Đang làm mới...';
        refreshBtn.disabled = true;
    }

    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

function exportDashboard() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Báo cáo Dashboard Admin - Trọ 365\n";
    csvContent += "Thời gian xuất: " + new Date().toLocaleString('vi-VN') + "\n\n";

    csvContent += "Thống kê tổng quan\n";
    csvContent += "Tổng bài đăng,<?= $stats['total_posts'] ?>\n";
    csvContent += "Bài đăng chờ duyệt,<?= $stats['pending_posts'] ?>\n";
    csvContent += "Bài đăng đã duyệt,<?= $stats['approved_posts'] ?>\n";
    csvContent += "Tổng người dùng,<?= $stats['total_users'] ?>\n";
    csvContent += "Tổng seller,<?= $stats['total_sellers'] ?>\n";
    csvContent += "Tổng lượt xem,<?= $stats['total_views'] ?>\n";

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "dashboard-report-" + new Date().toISOString().split('T')[0] + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}


</script>
</body>
</html>
