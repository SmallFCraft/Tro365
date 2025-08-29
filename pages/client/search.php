<?php
/**
 * Enhanced Search Page with Advanced Features
 * Tro365 - Website thuê trọ
 * Mobile-First Responsive Design with Glassmorphism UI
 */

use Tro365\Core\Database;
use Tro365\Services\LocationService;
use Tro365\Core\Auth;

$db = Database::getInstance();
$auth = new Auth();

// Get search parameters
$keyword = cleanInput($_GET['q'] ?? $_GET['keyword'] ?? '');
$location = cleanInput($_GET['location'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$minPrice = (int)($_GET['min_price'] ?? $_GET['price_from'] ?? 0);
$maxPrice = (int)($_GET['max_price'] ?? $_GET['price_to'] ?? 0);

// Location parameters
$province = (int)($_GET['province'] ?? 0);
$district = (int)($_GET['district'] ?? 0);
$ward = (int)($_GET['ward'] ?? 0);

// Area parameters
$areaFrom = (int)($_GET['area_from'] ?? 0);
$areaTo = (int)($_GET['area_to'] ?? 0);

// Room parameters
$rooms = (int)($_GET['rooms'] ?? 0);

// Additional filters (commented out for now to avoid errors)
// $hasParking = isset($_GET['parking']) ? (int)$_GET['parking'] : null;
// $hasWifi = isset($_GET['wifi']) ? (int)$_GET['wifi'] : null;
// $hasAC = isset($_GET['ac']) ? (int)$_GET['ac'] : null;
// $hasWasher = isset($_GET['washer']) ? (int)$_GET['washer'] : null;
// $hasFridge = isset($_GET['fridge']) ? (int)$_GET['fridge'] : null;
// $hasElevator = isset($_GET['elevator']) ? (int)$_GET['elevator'] : null;
// $hasSecurity = isset($_GET['security']) ? (int)$_GET['security'] : null;

// Date filters (commented out for now to avoid errors)
// $dateFrom = cleanInput($_GET['date_from'] ?? '');
// $dateTo = cleanInput($_GET['date_to'] ?? '');

// Sorting parameters
$sortBy = cleanInput($_GET['sort'] ?? 'newest');
$viewMode = cleanInput($_GET['view'] ?? 'grid');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = getPostsPerPage();
$offset = ($page - 1) * $limit;

// Build search query
$where = "bd.TrangThai = " . POST_STATUS_APPROVED;
$params = [];

if (!empty($keyword) && trim($keyword) !== '') {
    $where .= " AND (bd.TieuDe LIKE :keyword1 OR bd.NoiDung LIKE :keyword2 OR bd.DiaChi LIKE :keyword3)";
    $params['keyword1'] = '%' . $keyword . '%';
    $params['keyword2'] = '%' . $keyword . '%';
    $params['keyword3'] = '%' . $keyword . '%';
}

if (!empty($location) && trim($location) !== '') {
    $where .= " AND bd.DiaChi LIKE :location";
    $params['location'] = '%' . $location . '%';
}

if ($category > 0) {
    $where .= " AND bd.DanhMucID = :category";
    $params['category'] = $category;
}

if ($minPrice > 0) {
    $where .= " AND bd.Gia >= :minPrice";
    $params['minPrice'] = $minPrice;
}

if ($maxPrice > 0) {
    $where .= " AND bd.Gia <= :maxPrice";
    $params['maxPrice'] = $maxPrice;
}

// Location filters (specific province/district/ward)
if ($province > 0) {
    $where .= " AND bd.TinhThanhID = :province";
    $params['province'] = $province;
}

if ($district > 0) {
    $where .= " AND bd.QuanHuyenID = :district";
    $params['district'] = $district;
}

if ($ward > 0) {
    $where .= " AND bd.XaPhuongID = :ward";
    $params['ward'] = $ward;
}

// Area filters
if ($areaFrom > 0) {
    $where .= " AND bd.DienTich >= :areaFrom";
    $params['areaFrom'] = $areaFrom;
}

if ($areaTo > 0) {
    $where .= " AND bd.DienTich <= :areaTo";
    $params['areaTo'] = $areaTo;
}

// Room filters
if ($rooms > 0) {
    if ($rooms >= 4) {
        $where .= " AND bd.SoPhong >= :rooms";
        $params['rooms'] = 4;
    } else {
        $where .= " AND bd.SoPhong = :rooms";
        $params['rooms'] = $rooms;
    }
}

// Amenity filters (commented out for now to avoid errors)
/*
if ($hasParking !== null) {
    if ($hasParking) {
        $where .= " AND (bd.NoiDung LIKE '%chỗ đậu xe%' OR bd.NoiDung LIKE '%parking%' OR bd.NoiDung LIKE '%bãi xe%')";
    }
}

if ($hasWifi !== null) {
    if ($hasWifi) {
        $where .= " AND (bd.NoiDung LIKE '%wifi%' OR bd.NoiDung LIKE '%internet%' OR bd.NoiDung LIKE '%mạng%')";
    }
}

if ($hasAC !== null) {
    if ($hasAC) {
        $where .= " AND (bd.NoiDung LIKE '%điều hòa%' OR bd.NoiDung LIKE '%máy lạnh%' OR bd.NoiDung LIKE '%air%')";
    }
}

if ($hasWasher !== null) {
    if ($hasWasher) {
        $where .= " AND (bd.NoiDung LIKE '%máy giặt%' OR bd.NoiDung LIKE '%giặt%' OR bd.NoiDung LIKE '%washing%')";
    }
}

if ($hasFridge !== null) {
    if ($hasFridge) {
        $where .= " AND (bd.NoiDung LIKE '%tủ lạnh%' OR bd.NoiDung LIKE '%fridge%' OR bd.NoiDung LIKE '%refrigerator%')";
    }
}

if ($hasElevator !== null) {
    if ($hasElevator) {
        $where .= " AND (bd.NoiDung LIKE '%thang máy%' OR bd.NoiDung LIKE '%elevator%' OR bd.NoiDung LIKE '%lift%')";
    }
}

if ($hasSecurity !== null) {
    if ($hasSecurity) {
        $where .= " AND (bd.NoiDung LIKE '%bảo vệ%' OR bd.NoiDung LIKE '%an ninh%' OR bd.NoiDung LIKE '%security%')";
    }
}

// Date filters
if (!empty($dateFrom)) {
    $where .= " AND bd.NgayTao >= :dateFrom";
    $params['dateFrom'] = $dateFrom . ' 00:00:00';
}

if (!empty($dateTo)) {
    $where .= " AND bd.NgayTao <= :dateTo";
    $params['dateTo'] = $dateTo . ' 23:59:59';
}
*/

// Build ORDER BY clause based on sort parameter
$orderBy = "bd.NgayTao DESC"; // Default: newest first
switch ($sortBy) {
    case 'price_asc':
        $orderBy = "bd.Gia ASC, bd.NgayTao DESC";
        break;
    case 'price_desc':
        $orderBy = "bd.Gia DESC, bd.NgayTao DESC";
        break;
    case 'area_asc':
        $orderBy = "bd.DienTich ASC, bd.NgayTao DESC";
        break;
    case 'area_desc':
        $orderBy = "bd.DienTich DESC, bd.NgayTao DESC";
        break;
    case 'oldest':
        $orderBy = "bd.NgayTao ASC";
        break;
    case 'newest':
    default:
        $orderBy = "bd.NgayTao DESC";
        break;
}

// Get posts without user-specific data (for better caching)
$sql = "SELECT bd.*, dm.TenDM, kh.HoTen as NguoiDang
        FROM BaiDang bd
        LEFT JOIN DanhMuc dm ON bd.DanhMucID = dm.ID
        LEFT JOIN KhachHang kh ON bd.NguoiDangID = kh.ID
        WHERE {$where}
        ORDER BY {$orderBy}
        LIMIT :limit OFFSET :offset";

$params['limit'] = $limit;
$params['offset'] = $offset;

// Cache posts list briefly for performance (excluding user-specific data)
$cacheParams = $params;
unset($cacheParams['currentUserId']); // Remove user-specific param from cache key
$cacheKeySearch = 'search:posts:' . sha1(json_encode($cacheParams));
$posts = cache_get($cacheKeySearch, null, 60);
if ($posts === null) {
    $posts = $db->select($sql, $params);
    cache_set($cacheKeySearch, $posts);
}

// Add favorite status for current user (non-cached, always current)
if (!empty($posts) && $auth->isLoggedIn()) {
    $currentUserId = $auth->getCurrentUser()['ID'];
    foreach ($posts as &$post) {
        $favoriteResult = $db->selectOne(
            "SELECT ID FROM YeuThich WHERE KhachHangID = :userId AND BaiDangID = :postId",
            ['userId' => $currentUserId, 'postId' => $post['ID']]
        );
        $post['isFavorited'] = $favoriteResult !== false;
    }
    unset($post); // Break reference
} else {
    // Set all posts as not favorited for non-logged-in users
    foreach ($posts as &$post) {
        $post['isFavorited'] = false;
    }
    unset($post); // Break reference
}

// Add location names from API with caching per ID
if (!empty($posts)) {
    $locationService = new LocationService();
    $provinceCache = [];
    $districtCache = [];
    $wardCache = [];
    foreach ($posts as &$post) {
        if (!empty($post['TinhThanhID'])) {
            $pid = (int)$post['TinhThanhID'];
            if (!isset($provinceCache[$pid])) {
                $ck = 'loc:province:' . $pid;
                $provinceCache[$pid] = cache_get($ck, null, 86400);
                if ($provinceCache[$pid] === null) {
                    $provinceCache[$pid] = $locationService->getProvinceName($pid) ?: '';
                    cache_set($ck, $provinceCache[$pid]);
                }
            }
            $post['TenTT'] = $provinceCache[$pid];
        } else {
            $post['TenTT'] = '';
        }

        if (!empty($post['QuanHuyenID'])) {
            $did = (int)$post['QuanHuyenID'];
            if (!isset($districtCache[$did])) {
                $ck = 'loc:district:' . $did;
                $districtCache[$did] = cache_get($ck, null, 86400);
                if ($districtCache[$did] === null) {
                    $districtCache[$did] = $locationService->getDistrictName($did) ?: '';
                    cache_set($ck, $districtCache[$did]);
                }
            }
            $post['TenQH'] = $districtCache[$did];
        } else {
            $post['TenQH'] = '';
        }

        if (!empty($post['XaPhuongID'])) {
            $wid = (int)$post['XaPhuongID'];
            if (!isset($wardCache[$wid])) {
                $ck = 'loc:ward:' . $wid;
                $wardCache[$wid] = cache_get($ck, null, 86400);
                if ($wardCache[$wid] === null) {
                    $wardCache[$wid] = $locationService->getWardName($wid) ?: '';
                    cache_set($ck, $wardCache[$wid]);
                }
            }
            $post['TenXP'] = $wardCache[$wid];
        } else {
            $post['TenXP'] = '';
        }
    }
    unset($post); // IMPORTANT: break reference
}

// Count total posts
$countSql = "SELECT COUNT(*) as total
             FROM BaiDang bd
             WHERE {$where}";

$countParams = $cacheParams; // Use the cache-friendly params (without user-specific data)
unset($countParams['limit'], $countParams['offset']);
$countKey = 'search:count:' . sha1($countSql . json_encode($countParams));
$totalResult = cache_get($countKey, null, 60);
if ($totalResult === null) {
    $totalResult = $db->selectOne($countSql, $countParams);
    cache_set($countKey, $totalResult);
}
$total = (int)($totalResult['total'] ?? 0);
$totalPages = ceil($total / $limit);

// Get categories for filter (cache)
$categories = cache_get('search:categories', null, 600);
if ($categories === null) {
    $categories = $db->select("SELECT * FROM DanhMuc WHERE TrangThai = 1 ORDER BY ThuTu, TenDM");
    cache_set('search:categories', $categories);
}

// Get location service for dropdowns (cache provinces)
$locationService = new LocationService();
$provinces = cache_get('locations:provinces', null, 86400);
if ($provinces === null) {
    $provinces = $locationService->getProvinces();
    cache_set('locations:provinces', $provinces);
}

// Get flash message
$flash = getFlashMessage();

// Generate search URL for filters
function buildSearchUrl($params = []) {
    $currentParams = $_GET;
    $mergedParams = array_merge($currentParams, $params);

    // Remove empty parameters but keep 0 values for some fields
    $mergedParams = array_filter($mergedParams, function($value, $key) {
        // Keep 0 values for numeric fields that can be 0
        if (in_array($key, ['category', 'province', 'district', 'ward', 'rooms']) && $value === 0) {
            return false;
        }
        return $value !== '' && $value !== null;
    }, ARRAY_FILTER_USE_BOTH);

    return '/search?' . http_build_query($mergedParams);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm - <?= getWebsiteName() ?></title>
    <meta name="description" content="Tìm kiếm phòng trọ, nhà cho thuê với bộ lọc thông minh và giao diện hiện đại">
    <meta name="keywords" content="tìm kiếm phòng trọ, nhà cho thuê, bộ lọc, địa điểm">

    <!-- Preload critical resources -->
    <link rel="preload" href="/assets/css/client/main.css" as="style">
    <link rel="preload" href="/assets/css/client/layouts.css" as="style">

    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/client/main.css" rel="stylesheet">
    <link href="/assets/css/client/layouts.css" rel="stylesheet">

    <!-- View Mode Styles -->
    <style>
        /* Grid View (Default) */
        #resultsGrid[data-view-mode="grid"] {
            display: grid;
            grid-template-columns: repeat(3, 1fr); /* Back to 3 columns as requested */
            gap: 2rem; /* Good spacing for 3 columns */
        }

        #resultsGrid[data-view-mode="grid"] .result-item {
            display: block;
        }

        #resultsGrid[data-view-mode="grid"] .post-card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* List View */
        #resultsGrid[data-view-mode="list"] {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        #resultsGrid[data-view-mode="list"] .result-item {
            display: block;
        }

        #resultsGrid[data-view-mode="list"] .post-card {
            display: flex;
            flex-direction: row;
            align-items: stretch;
            height: auto;
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--glass-shadow);
        }

        #resultsGrid[data-view-mode="list"] .post-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(var(--primary-rgb), 0.15);
            border-color: rgba(var(--primary-rgb), 0.2);
        }

        #resultsGrid[data-view-mode="list"] .post-image-container {
            flex: 0 0 240px;
            height: 180px;
            position: relative;
            overflow: hidden;
        }

        #resultsGrid[data-view-mode="list"] .post-image-container img,
        #resultsGrid[data-view-mode="list"] .post-image-container .card-img-top {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0;
        }

        #resultsGrid[data-view-mode="list"] .post-content {
            flex: 1;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            background: transparent;
            border: none;
        }

        #resultsGrid[data-view-mode="list"] .post-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
        }

        #resultsGrid[data-view-mode="list"] .post-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        #resultsGrid[data-view-mode="list"] .post-title a:hover {
            color: var(--primary-color);
        }

        #resultsGrid[data-view-mode="list"] .post-description {
            flex: 1;
            margin-bottom: 1rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        #resultsGrid[data-view-mode="list"] .price-section {
            margin-top: auto;
            margin-bottom: 1rem;
        }

        #resultsGrid[data-view-mode="list"] .card-meta {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        #resultsGrid[data-view-mode="list"] .card-footer {
            padding: 1rem 1.5rem;
            background: transparent;
            border-top: 1px solid var(--glass-border);
            margin-top: auto;
        }

        /* Common Styles */
        .post-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 12px 12px 0 0;
        }

        .post-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .post-card:hover .post-image {
            transform: scale(1.05);
        }

        .category-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            z-index: 2;
        }

        /* Post content styling now handled by main.css post-card classes */

        /* Glassmorphism Filters Sidebar */
        .filters-sidebar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            position: sticky;
            top: 2rem;
        }

        .filters-sidebar .filter-header {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow:
                0 4px 20px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .filters-sidebar .filter-group {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            box-shadow:
                0 4px 20px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .filters-sidebar .filter-group:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            box-shadow:
                0 12px 40px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .filters-sidebar .filter-group h6 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filters-sidebar .filter-group h6 i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .filters-sidebar .form-control,
        .filters-sidebar .form-select {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1.5px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: var(--text-primary);
            transition: all 0.3s ease;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .filters-sidebar .form-control:focus,
        .filters-sidebar .form-select:focus {
            background: rgba(255, 255, 255, 0.18);
            border-color: var(--primary-color);
            box-shadow:
                0 0 0 0.2rem rgba(var(--primary-color-rgb), 0.25),
                inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .filters-sidebar .btn-outline-primary {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .filters-sidebar .btn-outline-primary:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }

        .filters-sidebar .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(var(--primary-color-rgb), 0.3);
            transition: all 0.3s ease;
        }

        .filters-sidebar .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(var(--primary-color-rgb), 0.4);
        }

        /* Mobile Responsive */
        @media (max-width: 575.98px) {
            #resultsGrid[data-view-mode="grid"] {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            #resultsGrid[data-view-mode="list"] .post-card {
                flex-direction: column;
            }

            #resultsGrid[data-view-mode="list"] .post-image-container {
                flex: none;
                height: 200px;
                border-radius: 16px 16px 0 0;
            }

            #resultsGrid[data-view-mode="list"] .post-content {
                padding: 1rem;
            }
        }

        @media (min-width: 576px) and (max-width: 767.98px) {
            #resultsGrid[data-view-mode="grid"] {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
            }

            #resultsGrid[data-view-mode="list"] .post-image-container {
                flex: 0 0 180px;
                height: 140px;
            }

            #resultsGrid[data-view-mode="list"] .post-content {
                padding: 1.25rem;
            }
        }

        @media (min-width: 768px) and (max-width: 991.98px) {
            #resultsGrid[data-view-mode="grid"] {
                grid-template-columns: repeat(2, 1fr); /* 2 columns on tablet */
                gap: 1.5rem;
            }

            #resultsGrid[data-view-mode="list"] .post-image-container {
                flex: 0 0 200px;
                height: 160px;
            }
        }

        @media (min-width: 992px) and (max-width: 1199.98px) {
            #resultsGrid[data-view-mode="grid"] {
                grid-template-columns: repeat(3, 1fr); /* 3 columns on large screens */
                gap: 2rem;
            }
        }

        @media (min-width: 1200px) {
            #resultsGrid[data-view-mode="grid"] {
                grid-template-columns: repeat(3, 1fr); /* 3 columns on extra large screens */
                gap: 2rem;
            }
        }
    </style>

</head>
<body>
    <?php include __DIR__ . '/../../includes/layouts/client/header.php'; ?>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-bg"></div>
        <div class="container">

            <div class="hero-content text-center">
                <h1 class="hero-title">
                    <i class="fas fa-search me-2"></i>
                    Tìm kiếm phòng trọ
                </h1>
                <p class="hero-subtitle">
                    Khám phá hàng nghìn căn hộ, phòng trọ phù hợp với nhu cầu của bạn
                </p>

                <!-- Search Form -->
                <form method="GET" action="/search" class="search-form" id="quickSearchForm">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <input type="text"
                                   class="form-control"
                                   name="q"
                                   value="<?= e($keyword) ?>"
                                   placeholder="Nhập từ khóa tìm kiếm..."
                                   autocomplete="off">
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <input type="text"
                                   class="form-control"
                                   name="location"
                                   value="<?= e($location) ?>"
                                   placeholder="Địa điểm..."
                                   autocomplete="off">
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <select class="form-select" name="category">
                                <option value="">Tất cả danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['ID'] ?>" <?= $category == $cat['ID'] ? 'selected' : '' ?>>
                                        <?= e($cat['TenDM']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <button type="submit" class="btn search-btn w-100">
                                <i class="fas fa-search me-2"></i>
                                <span class="d-none d-lg-inline">Tìm</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
               <div class="container my-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                <li class="breadcrumb-item active">Tìm kiếm</li>
            </ol>
        </nav>
    </div>                     
    <!-- Main Content -->
    <div class="container my-4">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === MSG_SUCCESS ? 'success' : 'danger' ?> alert-dismissible fade show">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="filters-sidebar">
                    <div class="filter-header">
                        <h5 class="mb-0">
                            <i class="fas fa-sliders-h me-2"></i>
                            Bộ lọc
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetAllFilters()">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>

                    <form method="GET" action="/search" id="advancedFiltersForm">
                        <!-- Preserve search terms -->
                        <input type="hidden" name="q" value="<?= e($keyword) ?>">
                        <input type="hidden" name="location" value="<?= e($location) ?>">
                        <input type="hidden" name="category" value="<?= $category ?>">

                        <!-- Price Range -->
                        <div class="filter-group">
                            <h6>
                                <i class="fas fa-money-bill-wave"></i>
                                Khoảng giá
                            </h6>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="min_price"
                                           value="<?= $minPrice ?: '' ?>"
                                           placeholder="Từ"
                                           min="0">
                                </div>
                                <div class="col-6">
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="max_price"
                                           value="<?= $maxPrice ?: '' ?>"
                                           placeholder="Đến"
                                           min="0">
                                </div>
                            </div>
                            <div class="d-grid gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-price-preset" data-min="0" data-max="2000000">< 2 triệu</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-price-preset" data-min="2000000" data-max="5000000">2-5 triệu</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-price-preset" data-min="5000000" data-max="10000000">5-10 triệu</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-price-preset" data-min="10000000" data-max="">> 10 triệu</button>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="filter-group">
                            <h6>
                                <i class="fas fa-map-marker-alt"></i>
                                Địa điểm
                            </h6>
                            <select class="form-select form-select-sm mb-2" name="province" id="filterProvince" data-lazy-load="true">
                                <option value="">Chọn tỉnh/thành</option>
                                <?php
                                // Only load popular provinces initially to reduce DOM size
                                $popularProvinceCodes = ['01', '79', '48', '31', '92', '36', '33', '77', '74', '75'];
                                $popularProvinces = array_filter($provinces, function($prov) use ($popularProvinceCodes) {
                                    return in_array($prov['ID'], $popularProvinceCodes);
                                });

                                foreach ($popularProvinces as $prov): ?>
                                    <option value="<?= $prov['ID'] ?>" <?= $province == $prov['ID'] ? 'selected' : '' ?>>
                                        <?= e($prov['TenTT']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <?php if (count($provinces) > count($popularProvinces)): ?>
                                    <option value="load_more" disabled>--- Xem thêm tỉnh thành ---</option>
                                <?php endif; ?>
                            </select>

                            <select class="form-select form-select-sm mb-2" name="district" id="filterDistrict">
                                <option value="">Chọn quận/huyện</option>
                            </select>

                            <select class="form-select form-select-sm" name="ward" id="filterWard">
                                <option value="">Chọn phường/xã</option>
                            </select>
                        </div>

                        <!-- Area -->
                        <div class="filter-group">
                            <h6>
                                <i class="fas fa-expand-arrows-alt"></i>
                                Diện tích (m²)
                            </h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="area_from"
                                           value="<?= $areaFrom ?: '' ?>"
                                           placeholder="Từ"
                                           min="0">
                                </div>
                                <div class="col-6">
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="area_to"
                                           value="<?= $areaTo ?: '' ?>"
                                           placeholder="Đến"
                                           min="0">
                                </div>
                            </div>
                        </div>

                        <!-- Apply Button -->
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>
                            Áp dụng bộ lọc
                        </button>
                    </form>
                </div>
            </div>

            <!-- Search Results -->
            <div class="col-lg-9 col-md-8">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">
                            Kết quả tìm kiếm
                            <?php if (!empty($keyword)): ?>
                                cho "<strong class="text-primary"><?= e($keyword) ?></strong>"
                            <?php endif; ?>
                        </h4>
                        <p class="text-muted mb-0">
                            <i class="fas fa-list-ul me-1"></i>
                            Tìm thấy <strong><?= number_format($total) ?></strong> kết quả
                            <?php if ($page > 1): ?>
                                - Trang <?= $page ?>/<?= $totalPages ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <!-- View Mode Toggle -->
                        <div class="btn-group" role="group">
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm <?= $viewMode === 'grid' ? 'active' : '' ?>"
                                    data-view="grid"
                                    title="Xem dạng lưới">
                                <i class="fas fa-th"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm <?= $viewMode === 'list' ? 'active' : '' ?>"
                                    data-view="list"
                                    title="Xem dạng danh sách">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>

                        <!-- Sort Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                    type="button"
                                    id="sortDropdown"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                <i class="fas fa-sort me-1"></i>
                                <?php
                                $sortLabels = [
                                    'newest' => 'Mới nhất',
                                    'oldest' => 'Cũ nhất',
                                    'price_asc' => 'Giá thấp → cao',
                                    'price_desc' => 'Giá cao → thấp',
                                    'area_asc' => 'Diện tích nhỏ → lớn',
                                    'area_desc' => 'Diện tích lớn → nhỏ'
                                ];
                                echo $sortLabels[$sortBy] ?? 'Mới nhất';
                                ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                                <li>
                                    <a class="dropdown-item <?= $sortBy === 'newest' ? 'active' : '' ?>"
                                       href="<?= buildSearchUrl(['sort' => 'newest', 'page' => 1]) ?>">
                                        <i class="fas fa-clock me-2"></i>Mới nhất
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $sortBy === 'oldest' ? 'active' : '' ?>"
                                       href="<?= buildSearchUrl(['sort' => 'oldest', 'page' => 1]) ?>">
                                        <i class="fas fa-history me-2"></i>Cũ nhất
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $sortBy === 'price_asc' ? 'active' : '' ?>"
                                       href="<?= buildSearchUrl(['sort' => 'price_asc', 'page' => 1]) ?>">
                                        <i class="fas fa-sort-amount-up me-2"></i>Giá thấp → cao
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $sortBy === 'price_desc' ? 'active' : '' ?>"
                                       href="<?= buildSearchUrl(['sort' => 'price_desc', 'page' => 1]) ?>">
                                        <i class="fas fa-sort-amount-down me-2"></i>Giá cao → thấp
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $sortBy === 'area_asc' ? 'active' : '' ?>"
                                       href="<?= buildSearchUrl(['sort' => 'area_asc', 'page' => 1]) ?>">
                                        <i class="fas fa-compress-arrows-alt me-2"></i>Diện tích nhỏ → lớn
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $sortBy === 'area_desc' ? 'active' : '' ?>"
                                       href="<?= buildSearchUrl(['sort' => 'area_desc', 'page' => 1]) ?>">
                                        <i class="fas fa-expand-arrows-alt me-2"></i>Diện tích lớn → nhỏ
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Results Content -->
                <?php if (empty($posts)): ?>
                    <div class="text-center py-5">
                        <div class="glass-card">
                            <i class="fas fa-search fa-4x text-muted mb-3"></i>
                            <h4>Không tìm thấy kết quả phù hợp</h4>
                            <p class="text-muted mb-4">
                                Hãy thử thay đổi từ khóa hoặc điều chỉnh bộ lọc tìm kiếm để có kết quả tốt hơn
                            </p>
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="/search" class="btn btn-outline-primary">
                                    <i class="fas fa-undo me-2"></i>Đặt lại tìm kiếm
                                </a>
                                <a href="/" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i>Về trang chủ
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div id="resultsGrid" data-view-mode="<?= $viewMode ?>">
                        <?php foreach ($posts as $i => $post): ?>
                            <div class="result-item" data-post-id="<?= $post['ID'] ?>">
                                <div class="post-card" data-post-url="/post/<?= $post['ID'] ?>">
                                    <!-- Post Image Container -->
                                    <div class="post-image-container position-relative">
                                        <?php if ($post['AnhDaiDien']): ?>
                                            <?php
                                                $orig = e($post['AnhDaiDien']);
                                                $pi = pathinfo($post['AnhDaiDien']);
                                                $thumbPath = ($pi['dirname'] ?? '') . '/thumb_' . ($pi['basename'] ?? '');
                                                $thumbSrc = $orig;
                                                $abs = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\') . $thumbPath;
                                                if (!empty($pi['dirname']) && file_exists($abs)) {
                                                    $thumbSrc = $thumbPath; // use thumbnail if exists
                                                }
                                                $isFirst = ($i === 0);
                                            ?>
                                            <img src="<?= $isFirst ? $thumbSrc : $thumbSrc ?>"
                                                 class="card-img-top"
                                                 alt="<?= e($post['TieuDe']) ?>"
                                                 width="300" height="200"
                                                 <?= $isFirst ? '' : 'loading="lazy"' ?> decoding="async" referrerpolicy="no-referrer" fetchpriority="<?= $isFirst ? 'high' : 'low' ?>">
                                        <?php else: ?>
                                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 220px;">
                                                <i class="fas fa-home fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Image Overlay -->
                                        <div class="image-overlay"></div>

                                        <!-- Category Badge -->
                                        <div class="position-absolute top-0 end-0 m-3">
                                            <?php if (!empty($post['TenDM'])): ?>
                                                <span class="category-badge">
                                                    <?= e($post['TenDM']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Views Badge -->
                                        <div class="position-absolute bottom-0 start-0 m-3">
                                            <span class="views-badge">
                                                <i class="fas fa-eye"></i>
                                                <?= number_format($post['LuotXem'] ?? 0) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="post-content card-body">
                                        <h3 class="post-title card-title">
                                            <a href="/post/<?= $post['ID'] ?>">
                                                <?= e($post['TieuDe']) ?>
                                            </a>
                                        </h3>

                                        <p class="post-description card-text">
                                            <?php
                                            // Generate excerpt from NoiDung since MoTa was removed
                                            $excerpt = '';
                                            if (!empty($post['NoiDung'])) {
                                                // Use MarkdownHelper to create clean excerpt
                                                $excerpt = \Tro365\Helpers\MarkdownHelper::createExcerpt($post['NoiDung'], 120);
                                            }
                                            echo e($excerpt);
                                            ?>
                                        </p>

                                        <div class="price-section">
                                            <span class="price"><?= formatCurrency($post['Gia']) ?>/tháng</span>
                                            <?php if ($post['DienTich']): ?>
                                                <span class="area">
                                                    <i class="fas fa-expand-arrows-alt me-1"></i>
                                                    <?= $post['DienTich'] ?>m²
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="card-meta">
                                            <div class="author">
                                                <i class="fas fa-user"></i>
                                                <?= e($post['NguoiDang']) ?>
                                            </div>
                                            <div class="date">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?= timeAgo($post['NgayTao']) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer">
                                        <div class="action-buttons">
                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <button class="btn-favorite <?= $post['isFavorited'] ? 'favorited' : '' ?>"
                                                        data-post-id="<?= $post['ID'] ?>"
                                                        onclick="toggleFavorite(<?= $post['ID'] ?>, this)"
                                                        title="<?= $post['isFavorited'] ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích' ?>">
                                                    <i class="<?= $post['isFavorited'] ? 'fas' : 'far' ?> fa-heart <?= $post['isFavorited'] ? 'text-danger' : '' ?>"></i>
                                                    <span class="d-none d-md-inline"><?= $post['isFavorited'] ? 'Đã yêu thích' : 'Yêu thích' ?></span>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-favorite" onclick="showToast('Vui lòng đăng nhập để sử dụng tính năng này','info'); setTimeout(function(){ redirectToLogin(); }, 1200); return false;" title="Đăng nhập để yêu thích">
                                                    <i class="far fa-heart"></i>
                                                    <span class="d-none d-md-inline">Yêu thích</span>
                                                </button>
                                            <?php endif; ?>
                                            <a href="/post/<?= $post['ID'] ?>" class="btn-view">
                                                <i class="fas fa-eye"></i>
                                                Xem chi tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Phân trang kết quả tìm kiếm" class="mt-4">
                            <div class="text-center mb-3">
                                <small class="text-muted">
                                    Hiển thị <?= ($page - 1) * $limit + 1 ?> - <?= min($page * $limit, $total) ?>
                                    trong tổng số <?= number_format($total) ?> kết quả
                                </small>
                            </div>

                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildSearchUrl(['page' => $page - 1]) ?>">
                                            <i class="fas fa-chevron-left"></i> Trước
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= buildSearchUrl(['page' => $i]) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= buildSearchUrl(['page' => $page + 1]) ?>">
                                            Sau <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize location dropdowns
            if (typeof NavigationModern !== 'undefined') {
                const navigation = new NavigationModern();

                // Load districts if province is selected
                const provinceSelect = document.getElementById('filterProvince');
                const districtSelect = document.getElementById('filterDistrict');
                const wardSelect = document.getElementById('filterWard');

                if (provinceSelect && provinceSelect.value) {
                    navigation.loadDistricts(provinceSelect.value).then(() => {
                        if (districtSelect && <?= $district ?>) {
                            districtSelect.value = <?= $district ?>;
                            if (wardSelect && <?= $ward ?>) {
                                navigation.loadWards(<?= $district ?>).then(() => {
                                    wardSelect.value = <?= $ward ?>;
                                });
                            }
                        }
                    });
                }

                // Province change handler
                provinceSelect?.addEventListener('change', function() {
                    navigation.loadDistricts(this.value);
                    wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
                });

                // District change handler
                districtSelect?.addEventListener('change', function() {
                    navigation.loadWards(this.value);
                });

                // Lazy load all provinces on focus to reduce initial DOM size
                provinceSelect?.addEventListener('focus', function() {
                    if (this.dataset.lazyLoad === 'true') {
                        loadAllProvincesForFilter(this);
                        this.dataset.lazyLoad = 'false';
                    }
                });
            }

            // Price preset buttons
            document.querySelectorAll('.btn-price-preset').forEach(btn => {
                btn.addEventListener('click', function() {
                    const minPrice = this.dataset.min;
                    const maxPrice = this.dataset.max;

                    document.querySelector('input[name="min_price"]').value = minPrice;
                    document.querySelector('input[name="max_price"]').value = maxPrice;

                    // Update active state
                    document.querySelectorAll('.btn-price-preset').forEach(b => b.classList.remove('btn-primary'));
                    document.querySelectorAll('.btn-price-preset').forEach(b => b.classList.add('btn-outline-primary'));
                    this.classList.remove('btn-outline-primary');
                    this.classList.add('btn-primary');
                });
            });

            // Reset filters function
            window.resetAllFilters = function() {
                // Create URL with only basic search parameters preserved
                const url = new URL(window.location.origin + '/search');

                // Preserve only basic search terms if they exist
                const currentParams = new URLSearchParams(window.location.search);
                const preserveParams = ['q', 'keyword', 'location'];

                preserveParams.forEach(param => {
                    const value = currentParams.get(param);
                    if (value && value.trim() !== '') {
                        url.searchParams.set(param, value);
                    }
                });

                // Redirect to clean search URL
                window.location.href = url.toString();
            };

            // View mode toggle
            document.querySelectorAll('[data-view]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const viewMode = this.dataset.view;
                    const url = new URL(window.location);
                    url.searchParams.set('view', viewMode);
                    window.location.href = url.toString();
                });
            });

            // Make post cards clickable
            document.querySelectorAll('.post-card[data-post-url]').forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking on buttons or links
                    if (e.target.closest('button, a')) {
                        return;
                    }

                    const url = this.dataset.postUrl;
                    if (url) {
                        window.location.href = url;
                    }
                });

                // Add cursor pointer
                card.style.cursor = 'pointer';
            });

            // Favorite buttons functionality - use standardized toggleFavorite
            document.querySelectorAll('.btn-favorite').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const postId = this.dataset.postId;
                    if (!postId) {
                        console.error('Post ID not found');
                        return;
                    }

                    // Check if user is logged in
                    <?php if (!$auth->isLoggedIn()): ?>
                        showToast('Vui lòng đăng nhập để sử dụng tính năng này', 'info');
                        setTimeout(() => {
                            window.location.href = '/login';
                        }, 1500);
                        return;
                    <?php endif; ?>

                    // Call the standardized toggle favorite function
                    toggleFavorite(postId, this);
                });
            });
        });

        // Toast notification (unified)
        function showToast(message, type = 'info', duration = 3000) {
            if (window.TroToast && typeof window.TroToast.show === 'function') {
                window.TroToast.show({ message, type, duration });
            } else {
                alert(message);
            }
        }


        // Global function for favorite toggle (copied from working home.php implementation)
        function toggleFavorite(postId, buttonElement) {
            // Check if user is logged in
            <?php if (!$auth->isLoggedIn()): ?>
            showToast('Vui lòng đăng nhập để sử dụng tính năng này', 'info');
            window.location.href = '/login';
            return;
            <?php else: ?>

            // Get button element if not provided
            if (!buttonElement) {
                buttonElement = event?.target?.closest('button');
            }

            if (!buttonElement) {
                console.error('Cannot find button element for favorite toggle');
                return;
            }

            // Prevent multiple clicks
            if (buttonElement.disabled) {
                return;
            }

            // Show loading state
            const heartIcon = buttonElement.querySelector('i');
            const textSpan = buttonElement.querySelector('span');
            const originalHeartClasses = heartIcon.className;
            const originalText = textSpan ? textSpan.textContent : '';

            // Set loading state
            buttonElement.disabled = true;
            heartIcon.className = 'fas fa-spinner fa-spin';
            if (textSpan) textSpan.textContent = 'Đang xử lý...';

            window.Tro365Common.toggleFavorite(postId, function(data) {
                // Restore button state
                buttonElement.disabled = false;

                if (data.success && data.data) {
                    // Update UI based on API response
                    if (data.data.favorited) {
                        // Show filled red heart
                        heartIcon.className = 'fas fa-heart text-danger';
                        buttonElement.classList.add('favorited');
                        buttonElement.title = 'Xóa khỏi yêu thích';
                        if (textSpan) textSpan.textContent = 'Đã yêu thích';
                        showToast('Đã thêm vào danh sách yêu thích', 'success');
                    } else {
                        // Show empty heart
                        heartIcon.className = 'far fa-heart';
                        heartIcon.classList.remove('text-danger');
                        buttonElement.classList.remove('favorited');
                        buttonElement.title = 'Thêm vào yêu thích';
                        if (textSpan) textSpan.textContent = 'Yêu thích';
                        showToast('Đã xóa khỏi danh sách yêu thích', 'info');
                    }
                } else {
                    // Restore original state on error
                    heartIcon.className = originalHeartClasses;
                    if (textSpan) textSpan.textContent = originalText;

                    // Show error message
                    const errorMsg = data.message || 'Có lỗi xảy ra, vui lòng thử lại';
                    showToast(errorMsg, 'error');
                }
            });
            <?php endif; ?>
        }

        // Function to redirect to login for non-logged-in users
        function redirectToLogin() {
            showToast('Vui lòng đăng nhập để sử dụng tính năng này', 'info');
            window.location.href = '/login';
        }

        // Fix Bootstrap dropdown not working
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdowns manually if needed
            const dropdownButton = document.getElementById('sortDropdown');
            const dropdownMenu = document.querySelector('.dropdown-menu');

            if (dropdownButton && dropdownMenu) {
                dropdownButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Toggle dropdown
                    const isShown = dropdownMenu.classList.contains('show');

                    if (isShown) {
                        dropdownMenu.classList.remove('show');
                        dropdownMenu.style.display = 'none';
                        dropdownButton.setAttribute('aria-expanded', 'false');
                    } else {
                        dropdownMenu.classList.add('show');
                        dropdownMenu.style.display = 'block';
                        dropdownButton.setAttribute('aria-expanded', 'true');
                    }
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dropdownButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                        dropdownMenu.style.display = 'none';
                        dropdownButton.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            // Function to load all provinces for filter dropdown
            function loadAllProvincesForFilter(select) {
                const allProvinces = <?= json_encode($provinces) ?>;
                const currentValue = select.value;

                // Clear existing options
                select.innerHTML = '<option value="">Chọn tỉnh/thành</option>';

                // Add all provinces
                allProvinces.forEach(function(province) {
                    const option = document.createElement('option');
                    option.value = province.ID;
                    option.textContent = province.TenTT;
                    if (province.ID === currentValue) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            }
        });
    </script>

</body>
</html>
