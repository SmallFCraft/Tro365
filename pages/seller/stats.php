<?php
/**
 * Seller Statistics Page
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
use Tro365\Models\Post;
use Tro365\Core\Database;

$auth = new Auth();
$post = new Post();
$db = Database::getInstance();

// Require seller role
$auth->requireSeller();

$currentUser = $auth->getCurrentUser();

// Get detailed statistics
$stats = [
    'total_posts' => $post->count(['user_id' => $currentUser['ID']]),
    'pending_posts' => $post->count(['user_id' => $currentUser['ID'], 'status' => POST_STATUS_PENDING]),
    'approved_posts' => $post->count(['user_id' => $currentUser['ID'], 'status' => POST_STATUS_APPROVED]),
    'rejected_posts' => $post->count(['user_id' => $currentUser['ID'], 'status' => POST_STATUS_REJECTED]),
    'total_views' => 0, // Will be calculated from posts
    'total_contacts' => 0 // Will be calculated from contacts
];

// Get posts with view counts
$allPosts = $post->getAll(1, 100, ['user_id' => $currentUser['ID']]);
foreach ($allPosts as $postItem) {
    $stats['total_views'] += $postItem['LuotXem'] ?? 0;
}

// Get monthly statistics (last 6 months)
$monthlyStats = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('m/Y', strtotime("-$i months"));
    
    $monthlyStats[] = [
        'month' => $monthName,
        'posts' => $post->count([
            'user_id' => $currentUser['ID'],
            'created_at >=' => $month . '-01',
            'created_at <' => date('Y-m-d', strtotime($month . '-01 +1 month'))
        ]),
        'views' => rand(10, 100) // Placeholder - should be calculated from actual data
    ];
}

// Set page variables for header
$pageTitle = 'Thống kê Seller';
$pageDescription = 'Thống kê chi tiết về bài đăng và hiệu suất';

// Custom CSS for stats page
$customCSS = '
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .progress-custom {
            height: 8px;
            border-radius: 10px;
        }
        .icon-stats {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }';

// Include header
include __DIR__ . '/../../includes/layouts/client/header.php';
?>

<!-- Additional CSS for Seller Stats -->
<link href="/assets/css/seller/seller-main.css" rel="stylesheet">
<link href="/assets/css/seller/seller-stats.css" rel="stylesheet">

    <div class="seller-stats-container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="/seller/dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Thống kê</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="seller-stats-header seller-fade-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="seller-stats-title">
                        <i class="fas fa-chart-bar"></i>
                        Thống kê chi tiết
                    </h2>
                    <p class="seller-stats-subtitle">
                        Theo dõi hiệu suất và phân tích dữ liệu bài đăng của bạn
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="seller-contact-btn" onclick="window.print()">
                        <i class="fas fa-print"></i>In báo cáo
                    </button>
                    <button class="seller-contact-btn" onclick="exportStats()">
                        <i class="fas fa-download"></i>Xuất Excel
                    </button>
                </div>
            </div>
        </div>

    <!-- Statistics Overview -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Total Posts -->
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card text-center">
                        <div class="icon-stats bg-primary mx-auto">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="stats-number"><?= $stats['total_posts'] ?></div>
                        <div class="stats-label">Tổng bài đăng</div>
                    </div>
                </div>

                <!-- Approved Posts -->
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card text-center">
                        <div class="icon-stats bg-success mx-auto">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-number"><?= $stats['approved_posts'] ?></div>
                        <div class="stats-label">Đã duyệt</div>
                    </div>
                </div>

                <!-- Pending Posts -->
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card text-center">
                        <div class="icon-stats bg-warning mx-auto">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stats-number"><?= $stats['pending_posts'] ?></div>
                        <div class="stats-label">Chờ duyệt</div>
                    </div>
                </div>

                <!-- Total Views -->
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stats-card text-center">
                        <div class="icon-stats bg-info mx-auto">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stats-number"><?= number_format($stats['total_views']) ?></div>
                        <div class="stats-label">Lượt xem</div>
                    </div>
                </div>
            </div>

            <!-- Performance Chart -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fas fa-chart-line me-2"></i>Biểu đồ hiệu suất 6 tháng</h5>
                        <canvas id="performanceChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="col-lg-4">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fas fa-percentage me-2"></i>Tỷ lệ duyệt bài</h5>
                        
                        <?php 
                        $approvalRate = $stats['total_posts'] > 0 ? ($stats['approved_posts'] / $stats['total_posts']) * 100 : 0;
                        $pendingRate = $stats['total_posts'] > 0 ? ($stats['pending_posts'] / $stats['total_posts']) * 100 : 0;
                        $rejectedRate = $stats['total_posts'] > 0 ? ($stats['rejected_posts'] / $stats['total_posts']) * 100 : 0;
                        ?>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Đã duyệt</span>
                                <span><?= number_format($approvalRate, 1) ?>%</span>
                            </div>
                            <div class="progress progress-custom">
                                <div class="progress-bar bg-success" style="width: <?= $approvalRate ?>%"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Chờ duyệt</span>
                                <span><?= number_format($pendingRate, 1) ?>%</span>
                            </div>
                            <div class="progress progress-custom">
                                <div class="progress-bar bg-warning" style="width: <?= $pendingRate ?>%"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Từ chối</span>
                                <span><?= number_format($rejectedRate, 1) ?>%</span>
                            </div>
                            <div class="progress progress-custom">
                                <div class="progress-bar bg-danger" style="width: <?= $rejectedRate ?>%"></div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <a href="/seller/posts" class="btn btn-primary btn-sm">
                                <i class="fas fa-list me-1"></i>Xem tất cả bài đăng
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="chart-container">
                        <h5 class="mb-4"><i class="fas fa-history me-2"></i>Hoạt động gần đây</h5>
                        
                        <?php if (empty($allPosts)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h6>Chưa có bài đăng nào</h6>
                                <p class="text-muted">Hãy tạo bài đăng đầu tiên của bạn</p>
                                <a href="/seller/posts/create" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Tạo bài đăng
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Bài đăng</th>
                                            <th>Trạng thái</th>
                                            <th>Lượt xem</th>
                                            <th>Ngày tạo</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($allPosts, 0, 10) as $postItem): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?= generateImageHtml(
                                                        $postItem['AnhDaiDien'],
                                                        'Ảnh bài đăng',
                                                        'rounded me-2',
                                                        ['style' => 'width: 40px; height: 40px; object-fit: cover;']
                                                    ) ?>
                                                    <div>
                                                        <h6 class="mb-0"><?= e($postItem['TieuDe']) ?></h6>
                                                        <small class="text-muted"><?= e($postItem['DiaChi']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    POST_STATUS_PENDING => 'warning',
                                                    POST_STATUS_APPROVED => 'success',
                                                    POST_STATUS_REJECTED => 'danger'
                                                ];
                                                $statusText = [
                                                    POST_STATUS_PENDING => 'Chờ duyệt',
                                                    POST_STATUS_APPROVED => 'Đã duyệt',
                                                    POST_STATUS_REJECTED => 'Từ chối'
                                                ];
                                                ?>
                                                <span class="badge bg-<?= $statusClass[$postItem['TrangThai']] ?>">
                                                    <?= $statusText[$postItem['TrangThai']] ?>
                                                </span>
                                            </td>
                                            <td><?= number_format($postItem['LuotXem'] ?? 0) ?></td>
                                            <td><?= date('d/m/Y', strtotime($postItem['NgayTao'])) ?></td>
                                            <td>
                                                <a href="/seller/posts/edit/<?= $postItem['ID'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Performance Chart
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthlyStats, 'month')) ?>,
                datasets: [{
                    label: 'Bài đăng',
                    data: <?= json_encode(array_column($monthlyStats, 'posts')) ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Lượt xem',
                    data: <?= json_encode(array_column($monthlyStats, 'views')) ?>,
                    borderColor: '#f093fb',
                    backgroundColor: 'rgba(240, 147, 251, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Export function - TODO: Implement proper Excel export functionality
        function exportStats() {
            // Show user-friendly message instead of alert
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white bg-info border-0';
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-info-circle me-2"></i>
                        Tính năng xuất Excel đang được phát triển. Vui lòng chờ cập nhật tiếp theo.
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            // Add to page and show
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove after shown
            toast.addEventListener('hidden.bs.toast', () => {
                document.body.removeChild(toast);
            });
        }
    </script>
