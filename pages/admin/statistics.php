<?php
/**
 * Admin Statistics Page
 * Tro365 - Website thuê trọ
 */

use Tro365\Core\Auth;
use Tro365\Core\Database;
use Tro365\Models\Post;
use Tro365\Models\Category;

$auth = new Auth();
$db = Database::getInstance();
$post = new Post();
$category = new Category();

// Require admin role
$auth->requireAdmin();

$currentUser = $auth->getCurrentUser();

// Get date range from filters
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today
$period = $_GET['period'] ?? 'month'; // month, week, year

// Validate dates
if (!strtotime($startDate) || !strtotime($endDate)) {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-d');
}

// General Statistics
$stats = [
    'total_posts' => $db->count('BaiDang', '1=1'),
    'approved_posts' => $db->count('BaiDang', 'TrangThai = 1'),
    'pending_posts' => $db->count('BaiDang', 'TrangThai = 0'),
    'rejected_posts' => $db->count('BaiDang', 'TrangThai = 2'),
    'total_users' => $db->count('KhachHang', '1=1'),
    'total_sellers' => $db->count('KhachHang', 'VaiTroID >= 2'),
    'active_users' => $db->count('KhachHang', 'TrangThai = 1'),
    'total_categories' => $db->count('DanhMuc', '1=1'),
    'active_categories' => $db->count('DanhMuc', 'TrangThai = 1')
];

// Views statistics
$viewsResult = $db->selectOne("SELECT SUM(LuotXem) as total_views FROM BaiDang");
$stats['total_views'] = (int)($viewsResult['total_views'] ?? 0);

// Revenue statistics (if transactions exist)
try {
    $revenueResult = $db->selectOne("
        SELECT
            COUNT(*) as total_transactions,
            SUM(CASE WHEN TrangThai = 'completed' THEN GiaThue * 0.05 ELSE 0 END) as total_commission
        FROM GiaoDich
        WHERE NgayTao BETWEEN :start_date AND :end_date
    ", [
        'start_date' => $startDate . ' 00:00:00',
        'end_date' => $endDate . ' 23:59:59'
    ]);

    $stats['total_transactions'] = (int)($revenueResult['total_transactions'] ?? 0);
    $stats['total_commission'] = (float)($revenueResult['total_commission'] ?? 0);
} catch (Exception $e) {
    // GiaoDich table might not exist yet
    $stats['total_transactions'] = 0;
    $stats['total_commission'] = 0;
}

// Posts by category
$categoryStats = $db->select("
    SELECT dm.TenDM, COUNT(bd.ID) as SoBaiDang, dm.TrangThai
    FROM DanhMuc dm
    LEFT JOIN BaiDang bd ON dm.ID = bd.DanhMucID
    GROUP BY dm.ID, dm.TenDM, dm.TrangThai
    ORDER BY SoBaiDang DESC
");

// Posts by status over time
$postsByDate = $db->select("
    SELECT 
        DATE(NgayTao) as NgayTao,
        COUNT(*) as TongSo,
        SUM(CASE WHEN TrangThai = 1 THEN 1 ELSE 0 END) as DaDuyet,
        SUM(CASE WHEN TrangThai = 0 THEN 1 ELSE 0 END) as ChoDuyet,
        SUM(CASE WHEN TrangThai = 2 THEN 1 ELSE 0 END) as TuChoi
    FROM BaiDang 
    WHERE NgayTao BETWEEN :start_date AND :end_date
    GROUP BY DATE(NgayTao)
    ORDER BY NgayTao DESC
    LIMIT 30
", [
    'start_date' => $startDate . ' 00:00:00',
    'end_date' => $endDate . ' 23:59:59'
]);

// User registrations over time
$usersByDate = $db->select("
    SELECT 
        DATE(NgayTao) as NgayTao,
        COUNT(*) as SoNguoiDung,
        SUM(CASE WHEN VaiTroID >= 2 THEN 1 ELSE 0 END) as SoSeller
    FROM KhachHang 
    WHERE NgayTao BETWEEN :start_date AND :end_date
    GROUP BY DATE(NgayTao)
    ORDER BY NgayTao DESC
    LIMIT 30
", [
    'start_date' => $startDate . ' 00:00:00',
    'end_date' => $endDate . ' 23:59:59'
]);

// Top viewed posts
$topPosts = $db->select("
    SELECT bd.TieuDe, bd.LuotXem, bd.Gia, dm.TenDM, kh.HoTen
    FROM BaiDang bd
    LEFT JOIN DanhMuc dm ON bd.DanhMucID = dm.ID
    LEFT JOIN KhachHang kh ON bd.NguoiDangID = kh.ID
    WHERE bd.TrangThai = 1
    ORDER BY bd.LuotXem DESC
    LIMIT 10
");

// Recent activities (if activity table exists)
$recentActivities = [];
try {
    $recentActivities = $db->select("
        SELECT hd.*, kh.HoTen
        FROM HoatDong hd
        LEFT JOIN KhachHang kh ON hd.KhachHangID = kh.ID
        WHERE hd.NgayTao BETWEEN :start_date AND :end_date
        ORDER BY hd.NgayTao DESC
        LIMIT 20
    ", [
        'start_date' => $startDate . ' 00:00:00',
        'end_date' => $endDate . ' 23:59:59'
    ]);
} catch (Exception $e) {
    // Activity table might not exist
}

$pageTitle = 'Thống kê hệ thống';
$pageDescription = 'Thống kê tổng quan về hoạt động hệ thống';

include_once __DIR__ . '/../../includes/layouts/admin/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="admin-sidebar">
                <?php include __DIR__ . '/../../includes/layouts/admin/sidebar.php'; ?>
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
                        <i class="fas fa-chart-bar me-1"></i>
                        Thống kê hệ thống
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-chart-bar me-3"></i>
                            Thống kê hệ thống
                        </h1>
                        <p class="text-muted mb-0">Tổng quan về hoạt động và hiệu suất hệ thống</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-success" onclick="exportStats()">
                            <i class="fas fa-download me-2"></i>Xuất báo cáo
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="refreshStats()">
                            <i class="fas fa-sync-alt me-2"></i>Làm mới
                        </button>
                        <button type="button" class="btn btn-primary" onclick="printReport()">
                            <i class="fas fa-print me-2"></i>In báo cáo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Date Filter -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Bộ lọc thời gian
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-4">
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-calendar-day me-1"></i>
                                Từ ngày
                            </label>
                            <input type="date" class="form-control" name="start_date" value="<?= $startDate ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-calendar-check me-1"></i>
                                Đến ngày
                            </label>
                            <input type="date" class="form-control" name="end_date" value="<?= $endDate ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                <i class="fas fa-clock me-1"></i>
                                Khoảng thời gian
                            </label>
                            <select class="form-select" name="period">
                                <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>📅 7 ngày qua</option>
                                <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>📊 30 ngày qua</option>
                                <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>📈 1 năm qua</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-filter me-2"></i>Áp dụng lọc
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetFilter()">
                                <i class="fas fa-undo me-2"></i>Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Overview Stats -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stats-card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['total_posts']) ?></h3>
                                    <p class="mb-0">Tổng bài đăng</p>
                                </div>
                                <div class="card-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small>
                                    <i class="fas fa-check me-1"></i>
                                    <?= $stats['approved_posts'] ?> đã duyệt
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stats-card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['total_users']) ?></h3>
                                    <p class="mb-0">Tổng người dùng</p>
                                </div>
                                <div class="card-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small>
                                    <i class="fas fa-store me-1"></i>
                                    <?= $stats['total_sellers'] ?> seller
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stats-card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['total_views']) ?></h3>
                                    <p class="mb-0">Tổng lượt xem</p>
                                </div>
                                <div class="card-icon">
                                    <i class="fas fa-eye"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small>
                                    <i class="fas fa-chart-line me-1"></i>
                                    Tất cả bài đăng
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card stats-card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h3 class="mb-1"><?= number_format($stats['total_commission'], 0) ?>đ</h3>
                                    <p class="mb-0">Hoa hồng</p>
                                </div>
                                <div class="card-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small>
                                    <i class="fas fa-handshake me-1"></i>
                                    <?= $stats['total_transactions'] ?> giao dịch
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <!-- Posts by Category Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Bài đăng theo danh mục
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Posts Over Time Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>
                                Bài đăng theo thời gian
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="postsChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Tables Row -->
            <div class="row mb-4">
                <!-- Top Posts -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-fire me-2"></i>
                                Bài đăng được xem nhiều nhất
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tiêu đề</th>
                                            <th>Danh mục</th>
                                            <th>Lượt xem</th>
                                            <th>Giá</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topPosts as $topPost): ?>
                                            <tr>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 200px;">
                                                        <?= e($topPost['TieuDe']) ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= e($topPost['HoTen'] ?? 'N/A') ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?= e($topPost['TenDM'] ?? 'N/A') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?= number_format($topPost['LuotXem']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong class="text-success">
                                                        <?= number_format($topPost['Gia']) ?>đ
                                                    </strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Hoạt động gần đây
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recentActivities)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-history fa-2x text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Chưa có hoạt động nào</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($recentActivities, 0, 10) as $activity): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong><?= e($activity['HoTen'] ?? 'N/A') ?></strong>
                                                    <span class="text-muted"><?= e($activity['MoTa'] ?? '') ?></span>
                                                </div>
                                                <small class="text-muted">
                                                    <?= date('H:i d/m', strtotime($activity['NgayTao'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Statistics -->
            <div class="row mb-4">
                <!-- Posts by Status -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-list-check me-2"></i>
                                Trạng thái bài đăng
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Đã duyệt</span>
                                    <strong class="text-success"><?= $stats['approved_posts'] ?></strong>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-success"
                                         style="width: <?= $stats['total_posts'] > 0 ? ($stats['approved_posts'] / $stats['total_posts'] * 100) : 0 ?>%"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Chờ duyệt</span>
                                    <strong class="text-warning"><?= $stats['pending_posts'] ?></strong>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-warning"
                                         style="width: <?= $stats['total_posts'] > 0 ? ($stats['pending_posts'] / $stats['total_posts'] * 100) : 0 ?>%"></div>
                                </div>
                            </div>

                            <div class="mb-0">
                                <div class="d-flex justify-content-between">
                                    <span>Từ chối</span>
                                    <strong class="text-danger"><?= $stats['rejected_posts'] ?></strong>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-danger"
                                         style="width: <?= $stats['total_posts'] > 0 ? ($stats['rejected_posts'] / $stats['total_posts'] * 100) : 0 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Statistics -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-users me-2"></i>
                                Thống kê người dùng
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Hoạt động</span>
                                    <strong class="text-success"><?= $stats['active_users'] ?></strong>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-success"
                                         style="width: <?= $stats['total_users'] > 0 ? ($stats['active_users'] / $stats['total_users'] * 100) : 0 ?>%"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Seller</span>
                                    <strong class="text-info"><?= $stats['total_sellers'] ?></strong>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-info"
                                         style="width: <?= $stats['total_users'] > 0 ? ($stats['total_sellers'] / $stats['total_users'] * 100) : 0 ?>%"></div>
                                </div>
                            </div>

                            <div class="mb-0">
                                <div class="d-flex justify-content-between">
                                    <span>Không hoạt động</span>
                                    <strong class="text-secondary"><?= $stats['total_users'] - $stats['active_users'] ?></strong>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-secondary"
                                         style="width: <?= $stats['total_users'] > 0 ? (($stats['total_users'] - $stats['active_users']) / $stats['total_users'] * 100) : 0 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Statistics -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-tags me-2"></i>
                                Thống kê danh mục
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Hoạt động</span>
                                    <strong class="text-success"><?= $stats['active_categories'] ?></strong>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-success"
                                         style="width: <?= $stats['total_categories'] > 0 ? ($stats['active_categories'] / $stats['total_categories'] * 100) : 0 ?>%"></div>
                                </div>
                            </div>

                            <div class="mb-0">
                                <div class="d-flex justify-content-between">
                                    <span>Tạm dừng</span>
                                    <strong class="text-secondary"><?= $stats['total_categories'] - $stats['active_categories'] ?></strong>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar bg-secondary"
                                         style="width: <?= $stats['total_categories'] > 0 ? (($stats['total_categories'] - $stats['active_categories']) / $stats['total_categories'] * 100) : 0 ?>%"></div>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Category Chart Data
    const categoryData = <?= json_encode($categoryStats) ?>;
    const categoryLabels = categoryData.map(item => item.TenDM);
    const categoryValues = categoryData.map(item => parseInt(item.SoBaiDang));

    // Posts Chart Data
    const postsData = <?= json_encode(array_reverse($postsByDate)) ?>;
    const postsLabels = postsData.map(item => {
        const date = new Date(item.NgayTao);
        return date.toLocaleDateString('vi-VN', { month: 'short', day: 'numeric' });
    });
    const postsValues = postsData.map(item => parseInt(item.TongSo));
    const approvedValues = postsData.map(item => parseInt(item.DaDuyet));
    const pendingValues = postsData.map(item => parseInt(item.ChoDuyet));

    // Category Pie Chart
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryValues,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40',
                    '#FF6384',
                    '#C9CBCF'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Posts Line Chart
    const postsCtx = document.getElementById('postsChart').getContext('2d');
    new Chart(postsCtx, {
        type: 'line',
        data: {
            labels: postsLabels,
            datasets: [
                {
                    label: 'Tổng bài đăng',
                    data: postsValues,
                    borderColor: '#36A2EB',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Đã duyệt',
                    data: approvedValues,
                    borderColor: '#4BC0C0',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                },
                {
                    label: 'Chờ duyệt',
                    data: pendingValues,
                    borderColor: '#FFCE56',
                    backgroundColor: 'rgba(255, 206, 86, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
});

// Export Statistics
function exportStats() {
    const startDate = '<?= $startDate ?>';
    const endDate = '<?= $endDate ?>';

    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Báo cáo thống kê hệ thống Trọ 365\n";
    csvContent += "Từ ngày: " + startDate + " đến " + endDate + "\n\n";

    csvContent += "Thống kê tổng quan\n";
    csvContent += "Tổng bài đăng,<?= $stats['total_posts'] ?>\n";
    csvContent += "Bài đăng đã duyệt,<?= $stats['approved_posts'] ?>\n";
    csvContent += "Bài đăng chờ duyệt,<?= $stats['pending_posts'] ?>\n";
    csvContent += "Bài đăng từ chối,<?= $stats['rejected_posts'] ?>\n";
    csvContent += "Tổng người dùng,<?= $stats['total_users'] ?>\n";
    csvContent += "Tổng seller,<?= $stats['total_sellers'] ?>\n";
    csvContent += "Tổng lượt xem,<?= $stats['total_views'] ?>\n";
    csvContent += "Tổng hoa hồng,<?= number_format($stats['total_commission'], 0) ?>đ\n";

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "thong-ke-" + startDate + "-" + endDate + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Refresh Statistics
function refreshStats() {
    const refreshBtn = document.querySelector('[onclick="refreshStats()"]');
    if (refreshBtn) {
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i>Đang làm mới...';
        refreshBtn.disabled = true;
    }

    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Reset Filter
function resetFilter() {
    window.location.href = '/admin/statistics';
}

// Print Report
function printReport() {
    window.print();
}

// Real-time clock
function updateClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('vi-VN');
    const dateString = now.toLocaleDateString('vi-VN');

    // Update page title with current time
    document.title = `Thống kê hệ thống - ${timeString}`;

    // Add live time indicator if not exists
    let timeIndicator = document.getElementById('liveTime');
    if (!timeIndicator) {
        timeIndicator = document.createElement('small');
        timeIndicator.id = 'liveTime';
        timeIndicator.className = 'text-muted ms-2';
        const pageHeader = document.querySelector('.page-header h1');
        if (pageHeader) {
            pageHeader.appendChild(timeIndicator);
        }
    }
    timeIndicator.innerHTML = `<i class="fas fa-clock me-1"></i>Cập nhật: ${timeString}`;
}

// Update clock every second
setInterval(updateClock, 1000);
updateClock();

// Auto refresh every 5 minutes
setInterval(function() {
    const refreshBtn = document.querySelector('[onclick="refreshStats()"]');
    if (refreshBtn && !refreshBtn.disabled) {
        refreshStats();
    }
}, 300000); // 5 minutes

// Animate numbers on page load
function animateNumbers() {
    const numbers = document.querySelectorAll('.stats-card h3');
    numbers.forEach(num => {
        const finalValue = parseInt(num.textContent.replace(/[^\d]/g, ''));
        if (finalValue > 0) {
            let currentValue = 0;
            const increment = finalValue / 50;
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }
                num.textContent = Math.floor(currentValue).toLocaleString('vi-VN');
            }, 30);
        }
    });
}

// Initialize animations
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(animateNumbers, 500);
});
</script>
</body>
</html>
