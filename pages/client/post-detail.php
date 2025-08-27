<?php
/**
 * Post Detail Page
 * Tro365 - Website thuê trọ
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';

use Tro365\Core\Database;
use Tro365\Core\Auth;
use Tro365\Models\Contact;
use Tro365\Services\LocationService;

$db = Database::getInstance();
$auth = new Auth();
$contact = new Contact();

$contactError = '';
$contactSuccess = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact') {
    try {
        if (!$auth->isLoggedIn()) {
            throw new Exception('Vui lòng đăng nhập để liên hệ');
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token bảo mật không hợp lệ');
        }

        $currentUser = $auth->getCurrentUser();
        $formPostId = (int)($_POST['post_id'] ?? 0);

        // Get post info
        $post = $db->selectOne(
            "SELECT ID, NguoiDangID, TieuDe FROM BaiDang WHERE ID = :id AND TrangThai = 'approved'",
            ['id' => $formPostId]
        );

        if (!$post) {
            throw new Exception('Bài đăng không tồn tại');
        }

        if ($post['NguoiDangID'] == $currentUser['ID']) {
            throw new Exception('Bạn không thể liên hệ về bài đăng của chính mình');
        }

        $contactData = [
            'BaiDangID' => $formPostId,
            'NguoiLienHeID' => $currentUser['ID'],
            'ChuNhaID' => $post['NguoiDangID'],
            'HoTen' => cleanInput($_POST['contact_name'] ?? $currentUser['HoTen']),
            'SDT' => cleanInput($_POST['contact_phone'] ?? ''),
            'Email' => cleanInput($_POST['contact_email'] ?? $currentUser['Email']),
            'TinNhan' => cleanInput($_POST['contact_message'] ?? ''),
            'PostTitle' => $post['TieuDe']
        ];

        // Validate required fields
        if (empty($contactData['HoTen']) || empty($contactData['SDT'])) {
            throw new Exception('Vui lòng nhập đầy đủ họ tên và số điện thoại');
        }

        if (!isValidPhone($contactData['SDT'])) {
            throw new Exception('Số điện thoại không hợp lệ');
        }

        $contactId = $contact->create($contactData);
        $contactSuccess = 'Gửi liên hệ thành công! Chủ nhà sẽ liên hệ với bạn sớm nhất có thể.';

    } catch (Exception $e) {
        $contactError = $e->getMessage();
    }
}

// Get post ID from URL
$postId = (int)($_REQUEST['current_id'] ?? 0);

if (!$postId) {
    http_response_code(404);
    include __DIR__ . '/../errors/404.php';
    exit;
}

// Get post details
$sql = "SELECT bd.*, dm.TenDM, kh.HoTen as NguoiDang, kh.SDT as SDTNguoiDang, kh.Email as EmailNguoiDang
        FROM BaiDang bd
        LEFT JOIN DanhMuc dm ON bd.DanhMucID = dm.ID
        LEFT JOIN KhachHang kh ON bd.NguoiDangID = kh.ID
        WHERE bd.ID = :id AND bd.TrangThai = :status";

$post = $db->selectOne($sql, [
    'id' => $postId,
    'status' => POST_STATUS_APPROVED
]);

if ($post) {
    // Get location names from API
    $locationService = new LocationService();
    $post['TenTT'] = $post['TinhThanhID'] ? $locationService->getProvinceName($post['TinhThanhID']) : '';
    $post['TenQH'] = $post['QuanHuyenID'] ? $locationService->getDistrictName($post['QuanHuyenID']) : '';
    $post['TenXP'] = $post['XaPhuongID'] ? $locationService->getWardName($post['XaPhuongID']) : '';
}

if (!$post) {
    http_response_code(404);
    include __DIR__ . '/../errors/404.php';
    exit;
}

// Update view count
$db->execute("UPDATE BaiDang SET LuotXem = LuotXem + 1 WHERE ID = :id", ['id' => $postId]);

// Get post images
$images = $db->select("SELECT * FROM HinhAnhBaiDang WHERE BaiDangID = :id ORDER BY ThuTu", ['id' => $postId]);

// Get related posts
$relatedPosts = $db->select("
    SELECT bd.*
    FROM BaiDang bd
    WHERE bd.ID != :id
    AND bd.TrangThai = :status
    AND (bd.DanhMucID = :category OR bd.TinhThanhID = :province)
    ORDER BY bd.NgayTao DESC
    LIMIT 6
", [
    'id' => $postId,
    'status' => POST_STATUS_APPROVED,
    'category' => $post['DanhMucID'],
    'province' => $post['TinhThanhID']
]);

// Add location names to related posts using LocationService
if (!empty($relatedPosts)) {
    $locationService = new LocationService();
    foreach ($relatedPosts as &$relatedPost) {
        if ($relatedPost['TinhThanhID']) {
            $province = $locationService->getProvinceById($relatedPost['TinhThanhID']);
            $relatedPost['TenTT'] = $province['name'] ?? '';
        }
        if ($relatedPost['QuanHuyenID']) {
            $district = $locationService->getDistrictById($relatedPost['QuanHuyenID']);
            $relatedPost['TenQH'] = $district['name'] ?? '';
        }
    }
}





// Check if user has favorited this post
$isFavorited = false;
if ($auth->isLoggedIn()) {
    $favorite = $db->selectOne("SELECT ID FROM YeuThich WHERE KhachHangID = :userId AND BaiDangID = :postId", [
        'userId' => $auth->getCurrentUser()['ID'],
        'postId' => $postId
    ]);
    $isFavorited = !empty($favorite);
}

// This contact handling is now done above
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($post['TieuDe']) ?> - <?= getWebsiteName() ?></title>
    <meta name="description" content="<?php
    // Generate excerpt from NoiDung since MoTa was removed
    $metaDescription = '';
    if (!empty($post['NoiDung'])) {
        $metaDescription = \Tro365\Helpers\MarkdownHelper::createExcerpt($post['NoiDung'], 160);
    }
    echo e($metaDescription);
    ?>">
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/client/main.css" rel="stylesheet">
    <link href="/assets/css/client/layouts.css" rel="stylesheet">

    <!-- Post Detail Styles -->
    <style>
        /* ===== POST DETAIL GLASSMORPHISM STYLES ===== */

        /* Main Container */
        .post-detail-container {
            background: var(--bg-primary);
            min-height: 100vh;
            padding: 2rem 0;
        }

        /* Breadcrumb Enhancement */
        .breadcrumb {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .breadcrumb-item a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color var(--transition-base);
        }

        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }

        .breadcrumb-item.active {
            color: var(--text-primary);
        }

        /* Image Gallery Container */
        .post-image-gallery {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
        }

        .post-image-gallery::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-glass);
            pointer-events: none;
            z-index: -1;
        }

        /* Main Image Container */
        .main-image-container {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-lg);
        }

        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            transition: transform var(--transition-slow);
            cursor: pointer;
        }

        .main-image:hover {
            transform: scale(1.02);
        }

        /* Fullscreen Button */
        .fullscreen-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            color: white;
            padding: 0.75rem;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all var(--transition-base);
            z-index: 10;
        }

        .fullscreen-btn:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
        }



        /* Thumbnail Carousel */
        .thumbnail-carousel {
            margin-top: 1.5rem;
            position: relative;
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1rem;
            box-shadow: var(--glass-shadow);
        }

        .thumbnail-container {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            gap: 0.75rem;
            padding: 0.75rem 0;
            scrollbar-width: none;
            -ms-overflow-style: none;
            position: relative;
        }

        .thumbnail-container::-webkit-scrollbar {
            display: none;
        }

        /* Add scroll indicators */
        .thumbnail-container::before,
        .thumbnail-container::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 20px;
            pointer-events: none;
            z-index: 2;
            transition: opacity var(--transition-base);
        }

        .thumbnail-container::before {
            left: 0;
            background: linear-gradient(to right, var(--glass-bg) 0%, transparent 100%);
        }

        .thumbnail-container::after {
            right: 0;
            background: linear-gradient(to left, var(--glass-bg) 0%, transparent 100%);
        }

        .thumbnail-item {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: all var(--transition-base);
            border: 3px solid transparent;
            flex-shrink: 0;
            width: 90px;
            height: 70px;
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
        }

        .thumbnail-item:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: var(--shadow-lg);
            border-color: rgba(var(--primary-rgb), 0.5);
        }

        .thumbnail-item.active {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.3), var(--shadow-md);
            transform: translateY(-2px);
        }

        .thumbnail-item.active::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .thumbnail-item.active::before {
            content: '\2713';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
            z-index: 4;
        }

        .thumbnail-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all var(--transition-base);
            background: var(--glass-bg);
            border-radius: 12px;
        }

        .thumbnail-img.lazy {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .thumbnail-img.loaded {
            opacity: 1;
        }

        .thumbnail-item:hover .thumbnail-img {
            transform: scale(1.05);
        }

        /* Carousel Navigation */
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 2px solid var(--glass-border);
            border-radius: 50%;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-base);
            color: var(--text-primary);
            font-size: 1.1rem;
            z-index: 10;
            box-shadow: var(--shadow-sm);
        }

        .carousel-nav:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-50%) scale(1.2);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }

        .carousel-nav.prev {
            left: -21px;
        }

        .carousel-nav.next {
            right: -21px;
        }

        .carousel-nav:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            pointer-events: none;
            transform: translateY(-50%) scale(0.9);
        }

        .carousel-nav:disabled:hover {
            background: var(--glass-bg);
            color: var(--text-primary);
            transform: translateY(-50%) scale(0.9);
            box-shadow: var(--shadow-sm);
        }

        /* Thumbnail Loading Skeleton */
        .thumbnail-skeleton {
            width: 80px;
            height: 60px;
            background: linear-gradient(90deg, var(--glass-bg) 25%, rgba(var(--primary-rgb), 0.1) 50%, var(--glass-bg) 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 12px;
            flex-shrink: 0;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* Thumbnail Counter */
        .thumbnail-counter {
            text-align: center;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: rgba(var(--primary-rgb), 0.1);
            border-radius: 20px;
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid rgba(var(--primary-rgb), 0.2);
        }

        .thumbnail-counter #visibleThumbnails {
            color: var(--primary-color);
            font-weight: 700;
        }

        /* Carousel Title */
        .carousel-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .carousel-title i {
            color: var(--primary-color);
            margin-right: 0.5rem;
        }

        .carousel-scroll-hint {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .carousel-scroll-hint i {
            font-size: 0.7rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        /* Post Info Section */
        .post-info-section {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
        }

        .post-info-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-glass);
            pointer-events: none;
            z-index: -1;
        }

        .post-title {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .post-location {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .post-location i {
            color: var(--primary-color);
        }

        .post-meta {
            color: var(--text-muted);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .post-meta i {
            color: var(--primary-color);
        }

        /* Action Buttons */
        .post-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-favorite {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            color: var(--text-primary);
            transition: all var(--transition-base);
            font-weight: 500;
        }

        .btn-favorite:hover {
            background: var(--danger-color);
            color: white;
            border-color: var(--danger-color);
            transform: translateY(-2px);
        }

        .btn-favorite.favorited {
            background: var(--danger-color);
            color: white;
            border-color: var(--danger-color);
        }

        .btn-share {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            color: var(--text-primary);
            transition: all var(--transition-base);
            font-weight: 500;
        }

        .btn-share:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        /* Post Details Grid */
        .post-details-grid {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
        }

        .post-details-grid::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-glass);
            pointer-events: none;
            z-index: -1;
        }

        .feature-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--glass-border);
            transition: all var(--transition-base);
        }

        .feature-item:last-child {
            border-bottom: none;
        }

        .feature-item:hover {
            background: rgba(var(--primary-rgb), 0.05);
            border-radius: 12px;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .feature-item i {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-color);
            color: white;
            border-radius: 8px;
            margin-right: 1rem;
            font-size: 0.9rem;
        }

        .feature-item strong {
            color: var(--text-primary);
            margin-right: 0.5rem;
        }

        .feature-item span {
            color: var(--text-secondary);
        }

        /* Content Sections */
        .content-section {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
        }

        .content-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-glass);
            pointer-events: none;
            z-index: -1;
        }

        .content-section h4 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .content-section h4::before {
            content: '';
            width: 4px;
            height: 24px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        /* Rich Content Styling */
        .rich-content {
            line-height: 1.7;
            color: var(--text-secondary);
        }

        .rich-content p {
            margin-bottom: 1.2rem;
        }

        .rich-content h1, .rich-content h2, .rich-content h3,
        .rich-content h4, .rich-content h5, .rich-content h6 {
            color: var(--text-primary);
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .rich-content ul, .rich-content ol {
            margin-bottom: 1.2rem;
            padding-left: 2rem;
        }

        .rich-content li {
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }

        .rich-content blockquote {
            border-left: 4px solid var(--primary-color);
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: var(--text-secondary);
        }

        .rich-content a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition-base);
        }

        .rich-content a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        /* Sidebar Styles */
        .sidebar-widget {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
            transition: all var(--transition-base);
        }

        .sidebar-widget::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-glass);
            pointer-events: none;
            z-index: -1;
        }

        .sidebar-widget:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        /* Price Widget */
        .price-widget {
            text-align: center;
            background: var(--gradient-primary);
            color: white;
            border: none;
        }

        .price-widget::before {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
        }

        .price-amount {
            font-size: clamp(2rem, 5vw, 3rem);
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .price-period {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 500;
        }

        /* Contact Widget */
        .contact-widget .sidebar-widget-title {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .contact-widget .sidebar-widget-title i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .contact-widget .form-control,
        .contact-widget .form-select,
        .contact-widget textarea {
            background: rgba(var(--primary-rgb), 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 0.875rem 1.25rem;
            color: var(--text-primary);
            transition: all var(--transition-base);
            font-size: 0.95rem;
        }

        .contact-widget .form-control:focus,
        .contact-widget .form-select:focus,
        .contact-widget textarea:focus {
            background: rgba(var(--primary-rgb), 0.08);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(var(--primary-rgb), 0.15);
            outline: none;
        }

        .contact-widget .form-label {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .btn-contact {
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            color: white;
            font-weight: 600;
            transition: all var(--transition-base);
            position: relative;
            overflow: hidden;
        }

        .btn-contact::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.4);
        }

        .btn-contact:hover::before {
            left: 100%;
        }

        .contact-direct {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .contact-direct .btn {
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all var(--transition-base);
        }

        .contact-direct .btn:hover {
            transform: translateY(-2px);
        }

        /* Related Posts Widget */
        .related-post-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--glass-border);
            transition: all var(--transition-base);
        }

        .related-post-item:last-child {
            border-bottom: none;
        }

        .related-post-item:hover {
            background: rgba(var(--primary-rgb), 0.05);
            border-radius: 12px;
            padding: 1rem;
            margin: 0 -1rem;
        }

        .related-post-thumb {
            width: 80px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .related-post-content {
            flex: 1;
            min-width: 0;
        }

        .related-post-title {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            line-height: 1.4;
            display: block;
            margin-bottom: 0.5rem;
            transition: color var(--transition-base);
        }

        .related-post-title:hover {
            color: var(--primary-color);
        }

        .related-post-price {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Related Posts Section */
        .related-posts-section {
            margin-top: 3rem;
        }

        .related-posts-section h4 {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
        }

        .related-posts-section h4::after {
            content: '';
            position: absolute;
            bottom: -0.5rem;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        .related-post-card {
            background: var(--glass-bg);
            backdrop-filter: var(--backdrop-filter);
            -webkit-backdrop-filter: var(--backdrop-filter);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all var(--transition-base);
            height: 100%;
            position: relative;
        }

        .related-post-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient-glass);
            pointer-events: none;
            z-index: -1;
        }

        .related-post-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: rgba(var(--primary-rgb), 0.3);
        }

        .related-post-card .card-img-top {
            height: 200px;
            object-fit: cover;
            transition: transform var(--transition-slow);
        }

        .related-post-card:hover .card-img-top {
            transform: scale(1.05);
        }

        .related-post-card .card-body {
            padding: 1.5rem;
        }

        .related-post-card .card-title a {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color var(--transition-base);
        }

        .related-post-card .card-title a:hover {
            color: var(--primary-color);
        }

        .related-post-card .card-text {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .related-post-card .text-primary {
            color: var(--primary-color) !important;
            font-weight: 700;
        }

        /* ===== LIGHTBOX ENHANCEMENTS ===== */
        .image-lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-slow);
        }

        .image-lightbox.active {
            opacity: 1;
            visibility: visible;
        }

        .lightbox-content {
            position: relative;
            width: min(95vw, 1280px);
            height: min(85vh, 900px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-image-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 100%;
            max-height: 100%;
        }

        .lightbox-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease, opacity 0.3s ease;
            cursor: grab;
        }

        .lightbox-image:active {
            cursor: grabbing;
        }

        .lightbox-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            z-index: 10;
        }

        .lightbox-close {
            position: absolute;
            top: -50px;
            right: -50px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all var(--transition-base);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all var(--transition-base);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-nav:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-50%) scale(1.1);
        }

        .lightbox-nav.prev {
            left: -70px;
        }

        .lightbox-nav.next {
            right: -70px;
        }

        .lightbox-counter {
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 500;
        }

        /* Zoom Controls */
        .lightbox-zoom-controls {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            z-index: 20;
        }

        .lightbox-zoom-controls button {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 44px;
            height: 44px;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: all var(--transition-base);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-zoom-controls button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .lightbox-zoom-controls button:active {
            transform: scale(0.95);
        }

        .lightbox-zoom-controls button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* ===== RESPONSIVE DESIGN ===== */

        /* Mobile Small (320px - 575px) */
        @media (max-width: 575.98px) {
            .post-detail-container {
                padding: 1rem 0;
            }

            .post-image-gallery,
            .post-info-section,
            .post-details-grid,
            .content-section,
            .sidebar-widget {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
                border-radius: 16px;
            }

            .main-image {
                height: 250px;
            }

            .post-title {
                font-size: 1.5rem;
            }

            .post-actions {
                justify-content: center;
            }

            .btn-favorite,
            .btn-share {
                flex: 1;
                text-align: center;
            }

            .thumbnail-item {
                width: 75px;
                height: 58px;
            }

            .carousel-nav {
                width: 36px;
                height: 36px;
                font-size: 0.9rem;
            }

            .carousel-nav.prev {
                left: -18px;
            }

            .carousel-nav.next {
                right: -18px;
            }

            .carousel-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .carousel-scroll-hint {
                font-size: 0.7rem;
            }

            .thumbnail-counter {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }

            .thumbnail-carousel {
                padding: 0.75rem;
                margin-top: 1rem;
            }

            .price-amount {
                font-size: 2rem;
            }

            .contact-direct {
                flex-direction: column;
            }

            .contact-direct .btn {
                width: 100%;
            }

            .related-post-item {
                flex-direction: column;
                text-align: center;
            }

            .related-post-thumb {
                width: 100%;
                height: 120px;
                align-self: center;
            }

            /* Lightbox Mobile */
            .lightbox-close {
                top: 20px;
                right: 20px;
                width: 44px;
                height: 44px;
                font-size: 1.1rem;
            }

            .lightbox-nav {
                width: 44px;
                height: 44px;
                font-size: 1rem;
            }

            .lightbox-nav.prev {
                left: 20px;
                bottom: 80px;
                top: auto;
                transform: none;
            }

            .lightbox-nav.next {
                right: 20px;
                bottom: 80px;
                top: auto;
                transform: none;
            }

            .lightbox-counter {
                bottom: 20px;
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .lightbox-zoom-controls {
                top: 80px;
                left: 20px;
                gap: 0.75rem;
            }

            .lightbox-zoom-controls button {
                width: 40px;
                height: 40px;
                font-size: 0.9rem;
            }

            .lightbox-image {
                border-radius: 8px;
            }
        }

        /* Mobile Large (576px - 767px) */
        @media (min-width: 576px) and (max-width: 767.98px) {
            .main-image {
                height: 300px;
            }

            .post-actions {
                justify-content: flex-start;
            }
        }

        /* Tablet (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .main-image {
                height: 350px;
            }
        }

        /* Desktop (992px+) */
        @media (min-width: 992px) {
            .sidebar-widget {
                position: sticky;
                top: 2rem;
            }
        }

        /* Large Desktop (1200px+) */
        @media (min-width: 1200px) {
            .main-image {
                height: 450px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/layouts/client/header.php'; ?>

    <div class="post-detail-container">
        <div class="container">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="/search">Tìm kiếm</a></li>
                    <li class="breadcrumb-item active"><?= e(truncateText($post['TieuDe'], 50)) ?></li>
                </ol>
            </nav>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Post Images Gallery -->
                <div class="post-image-gallery mb-4">
                    <?php if (!empty($images)): ?>
                        <!-- Main Image Container -->
                        <div class="main-image-container">
                            <?= generateImageHtml(
                                $images[0]['DuongDan'],
                                e($post['TieuDe']),
                                'main-image',
                                [
                                    'id' => 'mainImage',
                                    'data-gallery-image' => e($images[0]['DuongDan'])
                                ]
                            ) ?>

                            <button class="fullscreen-btn" aria-label="Xem toàn màn hình">
                                <i class="fas fa-expand"></i>
                            </button>


                        </div>

                        <!-- Thumbnail Carousel -->
                        <?php if (count($images) > 1): ?>
                            <div class="thumbnail-carousel">
                                <div class="carousel-title">
                                    <span><i class="fas fa-images"></i>Thư viện ảnh (<?= count($images) ?> ảnh)</span>
                                    <div class="carousel-scroll-hint">
                                        <i class="fas fa-arrows-alt-h"></i>
                                        <span>Vuốt để xem thêm</span>
                                    </div>
                                </div>
                                
                                <button class="carousel-nav prev" onclick="scrollThumbnails('prev')" disabled>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <div class="thumbnail-container" id="thumbnailContainer">
                                    <!-- Thumbnails will be populated by JavaScript -->
                                </div>
                                <button class="carousel-nav next" onclick="scrollThumbnails('next')">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                <div class="thumbnail-counter">
                                    <span id="visibleThumbnails">1</span> / <span id="totalThumbnails"><?= count($images) ?></span> ảnh
                                </div>
                            </div>

                            <!-- Hidden images for gallery data -->
                            <div style="display: none;">
                                <?php foreach ($images as $index => $image): ?>
                                    <img data-gallery-image="<?= e($image['DuongDan']) ?>"
                                         alt="Ảnh <?= $index + 1 ?>"
                                         data-index="<?= $index ?>">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php elseif (!empty($post['AnhDaiDien'])): ?>
                        <!-- Use cover image when no gallery images exist -->
                        <div class="main-image-container">
                            <?= generateImageHtml(
                                $post['AnhDaiDien'],
                                e($post['TieuDe']),
                                'main-image',
                                [
                                    'id' => 'mainImage',
                                    'data-gallery-image' => e($post['AnhDaiDien'])
                                ]
                            ) ?>

                            <button class="fullscreen-btn" aria-label="Xem toàn màn hình">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="main-image-container">
                            <?= getPostImage('', 'main-image', $post['TieuDe']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Post Info -->
                <div class="post-info-section">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div class="flex-grow-1">
                            <h1 class="post-title"><?= e($post['TieuDe']) ?></h1>
                            <div class="post-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>
                                    <?php
                                    $addressParts = array_filter([
                                        $post['DiaChi'],
                                        $post['TenXP'],
                                        $post['TenQH'],
                                        $post['TenTT']
                                    ]);
                                    echo e(implode(', ', $addressParts));
                                    ?>
                                </span>
                            </div>
                            <div class="post-meta">
                                <span><i class="fas fa-eye"></i> <?= number_format($post['LuotXem']) ?> lượt xem</span>
                                <span><i class="fas fa-clock"></i> <?= timeAgo($post['NgayTao']) ?></span>
                            </div>
                        </div>
                        <div class="post-actions">
                            <?php if ($auth->isLoggedIn()): ?>
                                <button class="btn btn-favorite <?= $isFavorited ? 'favorited' : '' ?>" onclick="toggleFavorite(<?= $postId ?>)">
                                    <i class="fas fa-heart"></i>
                                    <span id="favoriteText"><?= $isFavorited ? 'Đã yêu thích' : 'Yêu thích' ?></span>
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-share" onclick="sharePost()">
                                <i class="fas fa-share"></i> Chia sẻ
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Post Details -->
                <div class="post-details-grid">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="feature-item">
                                <i class="fas fa-tag"></i>
                                <span><strong>Danh mục:</strong> <?= e($post['TenDM']) ?></span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-expand-arrows-alt"></i>
                                <span><strong>Diện tích:</strong> <?= $post['DienTich'] ?>m² / phòng</span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-door-open"></i>
                                <span><strong>Số phòng:</strong> <?= $post['SoPhong'] ?> phòng</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="feature-item">
                                <i class="fas fa-user"></i>
                                <span><strong>Người đăng:</strong> <?= e($post['NguoiDang']) ?></span>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-calendar"></i>
                                <span><strong>Ngày đăng:</strong> <?= formatDate($post['NgayTao']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Post Content -->
                <?php if (!empty($post['MoTa'])): ?>
                <div class="content-section">
                    <h4><i class="fas fa-align-left"></i> Mô tả ngắn</h4>
                    <div class="rich-content">
                        <?= nl2br(e($post['MoTa'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($post['NoiDung'])): ?>
                <div class="content-section">
                    <h4><i class="fas fa-file-alt"></i> Mô tả chi tiết</h4>
                    <div class="rich-content">
                        <?= sanitizeHtml($post['NoiDung']) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($post['MoTa']) && empty($post['NoiDung'])): ?>
                <div class="content-section">
                    <h4><i class="fas fa-info-circle"></i> Mô tả</h4>
                    <div class="rich-content">
                        <p class="text-muted">Chưa có mô tả cho bài đăng này.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Price Widget -->
                <div class="sidebar-widget price-widget">
                    <div class="price-amount"><?= formatCurrency($post['Gia']) ?></div>
                    <div class="price-period">/ tháng</div>
                </div>



                <!-- Contact Widget -->
                <div class="sidebar-widget contact-widget">
                    <div class="sidebar-widget-title">
                        <i class="fas fa-phone"></i>
                        Liên hệ chủ trọ
                    </div>
                    
                    <?php if ($contactError): ?>
                        <div class="alert alert-danger alert-sm">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= e($contactError) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($contactSuccess): ?>
                        <div class="alert alert-success alert-sm">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= e($contactSuccess) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($auth->isLoggedIn()): ?>
                        <?php $currentUser = $auth->getCurrentUser(); ?>
                        <?php if ($post['NguoiDangID'] != $currentUser['ID']): ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="contact">
                                <input type="hidden" name="post_id" value="<?= $postId ?>">
                        
                                <div class="mb-3">
                                    <label class="form-label">Họ tên</label>
                                    <input type="text"
                                           class="form-control"
                                           name="contact_name"
                                           value="<?= e($currentUser['HoTen']) ?>"
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Số điện thoại</label>
                                    <input type="tel"
                                           class="form-control"
                                           name="contact_phone"
                                           value="<?= e($currentUser['SDT'] ?? '') ?>"
                                           placeholder="0987654321"
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email"
                                           class="form-control"
                                           name="contact_email"
                                           value="<?= e($currentUser['Email']) ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tin nhắn</label>
                                    <textarea class="form-control"
                                              name="contact_message"
                                              rows="3"
                                              placeholder="Tôi quan tâm đến bài đăng này. Xin vui lòng liên hệ với tôi..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-contact w-100">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Gửi liên hệ
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Đây là bài đăng của bạn
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            <a href="/login" class="alert-link">Đăng nhập</a> để liên hệ với chủ nhà
                        </div>
                    <?php endif; ?>
                    
                    <hr style="border-color: var(--glass-border); margin: 1.5rem 0;">

                    <div class="text-center">
                        <p class="mb-3" style="color: var(--text-secondary);"><strong>Hoặc liên hệ trực tiếp:</strong></p>
                        <div class="contact-direct">
                            <a href="tel:<?= e($post['SDTNguoiDang']) ?>" class="btn btn-success">
                                <i class="fas fa-phone me-1"></i>
                                Gọi ngay
                            </a>
                            <a href="mailto:<?= e($post['EmailNguoiDang']) ?>" class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-1"></i>
                                Email
                            </a>
                        </div>
                        <div class="mt-2">
                            <small style="color: var(--text-muted);"><?= e($post['SDTNguoiDang']) ?></small>
                        </div>
                    </div>
                </div>

                <!-- Related Posts Widget -->
                <?php if (!empty($relatedPosts)): ?>
                <div class="sidebar-widget">
                    <div class="sidebar-widget-title">
                        <i class="fas fa-layer-group"></i>
                        Bài đăng liên quan
                    </div>
                    <?php foreach (array_slice($relatedPosts, 0, 4) as $relatedPost): ?>
                    <div class="related-post-item">
                        <?php if (!empty($relatedPost['AnhDaiDien'])): ?>
                            <img src="<?= e($relatedPost['AnhDaiDien']) ?>" alt="<?= e($relatedPost['TieuDe']) ?>" class="related-post-thumb">
                        <?php else: ?>
                            <div class="related-post-thumb" style="background: var(--glass-bg); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-home" style="color: var(--text-muted);"></i>
                            </div>
                        <?php endif; ?>
                        <div class="related-post-content">
                            <a href="/post/<?= $relatedPost['ID'] ?>" class="related-post-title">
                                <?= e(truncateText($relatedPost['TieuDe'], 50)) ?>
                            </a>
                            <div class="related-post-price"><?= formatCurrency($relatedPost['Gia']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>






            </div>
        </div>

        </div>
    </div>

    <?php include __DIR__ . '/../../includes/layouts/client/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/client/image-gallery.js"></script>
    <script>
        
        function toggleFavorite(postId) {
            const buttonElement = document.querySelector('.btn-favorite');
            const heartIcon = buttonElement.querySelector('.fa-heart');
            const favoriteText = document.getElementById('favoriteText');

            // Prevent multiple clicks
            if (buttonElement.disabled) {
                return;
            }

            // Store original state for rollback
            const originalHeartClasses = heartIcon.className;
            const originalText = favoriteText.textContent;
            const originalButtonClasses = buttonElement.className;

            // Set loading state
            buttonElement.disabled = true;
            heartIcon.className = 'fas fa-spinner fa-spin';
            favoriteText.textContent = 'Đang xử lý...';
            buttonElement.classList.add('loading');

            // AJAX call to toggle favorite
            fetch('/api/toggle-favorite', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': '<?= csrf_token() ?>'
                },
                body: JSON.stringify({ postId: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    // Update UI based on API response
                    if (data.data.favorited) {
                        // Show filled red heart
                        heartIcon.className = 'fas fa-heart';
                        favoriteText.textContent = 'Đã yêu thích';
                        buttonElement.classList.add('favorited');
                        buttonElement.title = 'Xóa khỏi yêu thích';
                    } else {
                        // Show empty heart
                        heartIcon.className = 'fas fa-heart';
                        favoriteText.textContent = 'Yêu thích';
                        buttonElement.classList.remove('favorited');
                        buttonElement.title = 'Thêm vào yêu thích';
                    }

                    // Show success message briefly
                    const msg = (data.data && data.data.message)
                      || (data.data && data.data.favorited ? 'Đã thêm vào yêu thích' : 'Đã bỏ khỏi yêu thích');
                    showToast(msg, 'success');
                } else {
                    // Restore original state on error
                    heartIcon.className = originalHeartClasses;
                    favoriteText.textContent = originalText;
                    buttonElement.className = originalButtonClasses;

                    // Show error message
                    const errorMsg = data.message || 'Có lỗi xảy ra, vui lòng thử lại';
                    showToast(errorMsg, 'error');
                }
            })
            .catch(error => {
                console.error('Toggle favorite error:', error);

                // Restore original state on error
                heartIcon.className = originalHeartClasses;
                favoriteText.textContent = originalText;
                buttonElement.className = originalButtonClasses;

                showToast('Có lỗi kết nối, vui lòng thử lại', 'error');
            })
            .finally(() => {
                // Re-enable button
                buttonElement.disabled = false;
                buttonElement.classList.remove('loading');
            });
        }

        // Toast notification (unified)
        function showToast(message, type = 'info', duration = 3000) {
            if (window.TroToast && typeof window.TroToast.show === 'function') {
                window.TroToast.show({ message, type, duration });
            } else {
                alert(message);
            }
        }

        
        function sharePost() {
            if (navigator.share) {
                navigator.share({
                    title: '<?= e($post['TieuDe']) ?>',
                    text: '<?php
                    // Generate excerpt from NoiDung since MoTa was removed
                    $shareText = '';
                    if (!empty($post['NoiDung'])) {
                        $shareText = \Tro365\Helpers\MarkdownHelper::createExcerpt($post['NoiDung'], 100);
                    }
                    echo e($shareText);
                    ?>',
                    url: window.location.href
                }).then(() => {
                    showToast('Đã chia sẻ thành công!', 'success');
                }).catch(() => {
                    // Fallback to copy
                    copyToClipboard();
                });
            } else {
                copyToClipboard();
            }
        }

        function copyToClipboard() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    showToast('Đã sao chép link bài đăng!', 'success');
                }).catch(() => {
                    fallbackCopyToClipboard();
                });
            } else {
                fallbackCopyToClipboard();
            }
        }

        function fallbackCopyToClipboard() {
            const textArea = document.createElement('textarea');
            textArea.value = window.location.href;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                showToast('Đã sao chép link bài đăng!', 'success');
            } catch (err) {
                showToast('Không thể sao chép link', 'error');
            }

            document.body.removeChild(textArea);
        }

        // Enhanced form validation
        function validateContactForm() {
            const form = document.querySelector('form[method="POST"]');
            if (!form) return;

            const nameInput = form.querySelector('input[name="contact_name"]');
            const phoneInput = form.querySelector('input[name="contact_phone"]');
            const emailInput = form.querySelector('input[name="contact_email"]');

            // Real-time validation
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    const phoneRegex = /^[0-9]{10,11}$/;
                    const isValid = phoneRegex.test(this.value.replace(/\s/g, ''));

                    this.classList.toggle('is-valid', isValid && this.value.length > 0);
                    this.classList.toggle('is-invalid', !isValid && this.value.length > 0);
                });
            }

            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    const isValid = emailRegex.test(this.value);

                    this.classList.toggle('is-valid', isValid && this.value.length > 0);
                    this.classList.toggle('is-invalid', !isValid && this.value.length > 0);
                });
            }

            // Form submission enhancement
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang gửi...';

                    // Re-enable after 5 seconds as fallback
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Gửi liên hệ';
                    }, 5000);
                }
            });
        }

        // Smooth scroll to sections
        function smoothScrollTo(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Image lazy loading enhancement
        function enhanceImageLoading() {
            const images = document.querySelectorAll('img[data-src]');

            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                images.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback for older browsers
                images.forEach(img => {
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                });
            }
        }

        // Thumbnail Carousel Functions
        function scrollThumbnails(direction) {
            const container = document.getElementById('thumbnailContainer');
            const scrollAmount = 240; // ~3 thumbnails

            if (!container) return;

            if (direction === 'prev') {
                container.scrollLeft -= scrollAmount;
            } else {
                container.scrollLeft += scrollAmount;
            }

            // Update UI states
            updateCarouselButtons();
            updateVisibleThumbnailsCounter();
        }

        function updateCarouselButtons() {
            const container = document.getElementById('thumbnailContainer');
            const prevBtn = document.querySelector('.carousel-nav.prev');
            const nextBtn = document.querySelector('.carousel-nav.next');

            if (container && prevBtn && nextBtn) {
                prevBtn.disabled = container.scrollLeft <= 0;
                nextBtn.disabled = container.scrollLeft >= (container.scrollWidth - container.clientWidth);
            }
        }

        function initThumbnailCarousel() {
            const container = document.getElementById('thumbnailContainer');
            if (!container) return;

            // Add scroll event listener
            container.addEventListener('scroll', () => {
                updateCarouselButtons();
                updateVisibleThumbnailsCounter();
            });

            // Observe DOM changes to thumbnails and update counter/buttons accordingly
            if ('MutationObserver' in window) {
                const mo = new MutationObserver(() => {
                    updateCarouselButtons();
                    updateVisibleThumbnailsCounter();
                });
                mo.observe(container, { childList: true });
            }

            // Initialize lazy loading for thumbnails
            const thumbnails = container.querySelectorAll('.thumbnail-img.lazy');

            if ('IntersectionObserver' in window) {
                const thumbnailObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.classList.remove('lazy');
                                img.classList.add('loaded');
                                thumbnailObserver.unobserve(img);
                            }
                        }
                    });
                }, { rootMargin: '50px' });

                thumbnails.forEach(img => thumbnailObserver.observe(img));
            } else {
                // Fallback for older browsers
                thumbnails.forEach(img => {
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                    }
                });
            }

            // Update visible thumbnails counter
            updateVisibleThumbnailsCounter();
        }

        function updateVisibleThumbnailsCounter() {
            const container = document.getElementById('thumbnailContainer');
            const visibleSpan = document.getElementById('visibleThumbnails');
            const totalSpan = document.getElementById('totalThumbnails');

            if (container && visibleSpan) {
                // Use only thumbnail items to determine current index
                const thumbs = Array.from(container.querySelectorAll('.thumbnail-item'));
                let activeIndex = thumbs.findIndex(el => el.classList.contains('active'));
                if (activeIndex === -1) activeIndex = 0; // fallback to first

                visibleSpan.textContent = activeIndex + 1;
                if (totalSpan) totalSpan.textContent = thumbs.length;
            }
        }

        // Initialize enhanced features
        document.addEventListener('DOMContentLoaded', function() {
            validateContactForm();
            enhanceImageLoading();
            initThumbnailCarousel();

            // Update carousel on window resize
            window.addEventListener('resize', () => {
                updateCarouselButtons();
                updateVisibleThumbnailsCounter();
            });

            // Add smooth animations to elements
            const animatedElements = document.querySelectorAll('.post-info-section, .post-details-grid, .content-section, .sidebar-widget');

            if ('IntersectionObserver' in window) {
                const animationObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }
                    });
                }, { threshold: 0.1 });

                animatedElements.forEach(el => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    animationObserver.observe(el);
                });
            }
        });
    </script>
</body>
</html>
