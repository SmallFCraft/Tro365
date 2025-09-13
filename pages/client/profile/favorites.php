<?php
/**
 * User Favorites Page
 * Tro365 - Website thuê trọ
 */

// Performance optimization includes
require_once __DIR__ . '/../../../includes/performance/optimization.php';

use Tro365\Core\Auth;
use Tro365\Core\Database;
use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

$auth = new Auth();
$db = Database::getInstance();

// Require login
if (!$auth->isLoggedIn()) {
    setFlashMessage(MSG_ERROR, 'Vui lòng đăng nhập để xem danh sách yêu thích');
    redirect('/login');
}

$currentUser = $auth->getCurrentUser();
$userId = $currentUser['ID'];

// Pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 12;
$offset = ($page - 1) * $limit;

// Get favorites with post details
$favoritesQuery = "
    SELECT
        yt.ID as FavoriteID,
        yt.NgayTao as FavoriteDate,
        bd.*,
        dm.TenDM as TenDanhMuc,
        kh.HoTen as NguoiDang,
        kh.SDT as SoDienThoai,
        kh.Email as EmailNguoiDang,
        (SELECT COUNT(*) FROM YeuThich WHERE BaiDangID = bd.ID) as TotalFavorites,
        (SELECT DuongDan FROM HinhAnhBaiDang WHERE BaiDangID = bd.ID ORDER BY ThuTu ASC LIMIT 1) as HinhAnhChinh,
        bd.AnhDaiDien
    FROM YeuThich yt
    INNER JOIN BaiDang bd ON yt.BaiDangID = bd.ID
    INNER JOIN DanhMuc dm ON bd.DanhMucID = dm.ID
    INNER JOIN KhachHang kh ON bd.NguoiDangID = kh.ID
    WHERE yt.KhachHangID = ?
    AND bd.TrangThai = ?
    ORDER BY yt.NgayTao DESC
    LIMIT $limit OFFSET $offset
";

// Cache favorites data
$cacheKey = 'user_favorites_' . $userId . '_page_' . $page;
$favorites = cache_get($cacheKey);

if ($favorites === null) {
    $favorites = $db->select($favoritesQuery, [
        $userId,
        POST_STATUS_APPROVED
    ]);
    // Cache for 3 minutes (favorites change moderately)
    cache_set($cacheKey, $favorites, 180);
}

// Get total count for pagination - with caching
$totalCacheKey = 'user_favorites_count_' . $userId;
$total = cache_get($totalCacheKey);

if ($total === null) {
    $totalQuery = "
        SELECT COUNT(*) as total
        FROM YeuThich yt
        INNER JOIN BaiDang bd ON yt.BaiDangID = bd.ID
        WHERE yt.KhachHangID = ?
        AND bd.TrangThai = ?
    ";

    $totalResult = $db->selectOne($totalQuery, [
        $userId,
        POST_STATUS_APPROVED
    ]);

    $total = $totalResult['total'] ?? 0;
    cache_set($totalCacheKey, $total, 180);
}
$totalPages = ceil($total / $limit);

// Custom CSS for favorites page
$customCSS = '
<style>
.favorites-header {
    background: var(--gradient-primary);
    color: white;
    padding: 3rem 0;
    position: relative;
    overflow: hidden;
}

.favorites-header::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.05"%3E%3Ccircle cx="30" cy="30" r="4"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.3;
}

.favorites-stats {
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-filter);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 2rem;
    margin-top: -2rem;
    position: relative;
    z-index: 10;
    box-shadow: var(--glass-shadow);
}

.favorite-card {
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-filter);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
    height: 100%;
    box-shadow: var(--glass-shadow);
}

.favorite-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--gradient-glass);
    pointer-events: none;
    z-index: -1;
}

.favorite-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.4);
    border-color: rgba(var(--primary-rgb), 0.3);
}

.favorite-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.favorite-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.favorite-card:hover .favorite-image img {
    transform: scale(1.05);
}

.favorite-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.favorite-content {
    padding: 1.5rem;
}

.favorite-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-primary);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.favorite-location {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.favorite-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.favorite-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--glass-border);
    font-size: 0.85rem;
    color: var(--text-muted);
}

.favorite-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-remove-favorite {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #dc3545;
    border-radius: 12px;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.btn-remove-favorite:hover {
    background: rgba(220, 53, 69, 0.2);
    border-color: rgba(220, 53, 69, 0.5);
    color: #dc3545;
    transform: translateY(-2px);
}

.empty-favorites {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-filter);
    border: 1px solid var(--glass-border);
    border-radius: 24px;
    box-shadow: var(--glass-shadow);
}

.empty-icon {
    font-size: 4rem;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
}

.pagination-glass {
    background: var(--glass-bg);
    backdrop-filter: var(--backdrop-filter);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    padding: 1rem;
    box-shadow: var(--glass-shadow);
}

.page-link {
    background: transparent;
    border: 1px solid var(--glass-border);
    color: var(--text-primary);
    border-radius: 12px;
    margin: 0 2px;
    transition: all 0.3s ease;
}

.page-link:hover {
    background: var(--glass-bg);
    border-color: var(--primary-color);
    color: var(--primary-color);
    transform: translateY(-2px);
}

.page-item.active .page-link {
    background: var(--gradient-primary);
    border-color: var(--primary-color);
    color: white;
}

@media (max-width: 768px) {
    .favorites-header {
        padding: 2rem 0;
    }
    
    .favorites-stats {
        margin-top: -1rem;
        padding: 1.5rem;
        border-radius: 16px;
    }
    
    .favorite-card {
        border-radius: 16px;
    }
    
    .favorite-content {
        padding: 1.25rem;
    }
    
    .favorite-image {
        height: 180px;
    }
}
</style>';

// Include header
include __DIR__ . '/../../../includes/layouts/client/header.php';
?>

<div class="container-fluid">

    <div class="container my-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="/profile">Hồ sơ cá nhân</a></li>
                <li class="breadcrumb-item active">Yêu thích</li>
            </ol>
        </nav>

        <!-- Statistics -->
        <div class="favorites-stats mb-4">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="h4 text-primary mb-1"><?= number_format($total) ?></div>
                    <small class="text-muted">Tổng yêu thích</small>
                </div>
                <div class="col-md-4">
                    <div class="h4 text-success mb-1"><?= $totalPages ?></div>
                    <small class="text-muted">Trang</small>
                </div>
                <div class="col-md-4">
                    <div class="h4 text-info mb-1"><?= $page ?></div>
                    <small class="text-muted">Trang hiện tại</small>
                </div>
            </div>
        </div>

        <?php if (empty($favorites)): ?>
            <!-- Empty State -->
            <div class="empty-favorites">
                <div class="empty-icon">
                    <i class="fas fa-heart-broken"></i>
                </div>
                <h3 class="mb-3">Chưa có bài đăng yêu thích</h3>
                <p class="text-muted mb-4">
                    Bạn chưa lưu bài đăng nào vào danh sách yêu thích.<br>
                    Hãy khám phá và tìm kiếm những phòng trọ phù hợp!
                </p>
                <a href="/search" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>
                    Tìm kiếm phòng trọ
                </a>
            </div>
        <?php else: ?>
            <!-- Favorites Grid -->
            <div class="row g-4 mb-4">
                <?php foreach ($favorites as $favorite): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="favorite-card">
                            <div class="favorite-image">
                                <?= generateImageHtml(
                                    $favorite['HinhAnhChinh'] ?: $favorite['AnhDaiDien'] ?: '/assets/images/default/no-image.png',
                                    e($favorite['TieuDe']),
                                    'img-fluid',
                                    ['loading' => 'lazy']
                                ) ?>
                                <div class="favorite-badge">
                                    <i class="fas fa-heart me-1"></i>
                                    Yêu thích
                                </div>
                            </div>
                            
                            <div class="favorite-content">
                                <h5 class="favorite-title">
                                    <a href="/post/<?= $favorite['ID'] ?>" class="text-decoration-none">
                                        <?= e($favorite['TieuDe']) ?>
                                    </a>
                                </h5>
                                
                                <div class="favorite-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= e($favorite['DiaChi']) ?>
                                </div>
                                
                                <div class="favorite-price">
                                    <?= formatCurrency($favorite['Gia']) ?>/tháng
                                </div>
                                
                                <div class="favorite-meta">
                                    <small>
                                        <i class="fas fa-clock me-1"></i>
                                        <?= timeAgo($favorite['FavoriteDate']) ?>
                                    </small>
                                    <div class="favorite-actions">
                                        <button class="btn btn-remove-favorite btn-sm" 
                                                onclick="removeFavorite(<?= $favorite['ID'] ?>)"
                                                title="Xóa khỏi yêu thích">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <a href="/post/<?= $favorite['ID'] ?>" 
                                           class="btn btn-primary btn-sm"
                                           title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-glass">
                    <nav aria-label="Favorites pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">
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

<?php include __DIR__ . '/../../../includes/layouts/client/footer.php'; ?>

<script>
// Remove favorite function
async function removeFavorite(postId) {
    if (!confirm('Bạn có chắc chắn muốn xóa bài đăng này khỏi danh sách yêu thích?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/favorites/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ postId: postId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (window.TroToast && typeof window.TroToast.show === 'function') {
                window.TroToast.show({ message: 'Đã bỏ yêu thích', type: 'success', duration: 2000 });
            }
            // Reload page to update the list
            window.location.reload();
        } else {
            if (window.TroToast && typeof window.TroToast.show === 'function') {
                window.TroToast.show({ message: 'Có lỗi xảy ra. Vui lòng thử lại.', type: 'error', duration: 3000 });
            } else {
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            }
        }
    } catch (error) {
        console.error('Error removing favorite:', error);
        if (window.TroToast && typeof window.TroToast.show === 'function') {
            window.TroToast.show({ message: 'Có lỗi xảy ra. Vui lòng thử lại.', type: 'error', duration: 3000 });
        } else {
            alert('Có lỗi xảy ra. Vui lòng thử lại.');
        }
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Favorites page initialized');
});
</script>

