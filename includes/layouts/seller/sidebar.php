<?php
/**
 * Seller Sidebar Navigation
 * Tro365 - Website thuê trọ
 */

// Get current page for active menu highlighting
$currentPage = $_SERVER['REQUEST_URI'];
$currentPage = parse_url($currentPage, PHP_URL_PATH);

function isActive($path) {
    global $currentPage;
    return strpos($currentPage, $path) !== false ? 'active' : '';
}
?>

<div class="col-lg-3">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-store me-2"></i>
                Dashboard Seller
            </h5>
        </div>
        <div class="list-group list-group-flush">
            <a href="/seller" class="list-group-item list-group-item-action <?= isActive('/seller') && $currentPage === '/seller' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt me-2"></i>
                Tổng quan
            </a>
            
            <a href="/seller/posts" class="list-group-item list-group-item-action <?= isActive('/seller/posts') ? 'active' : '' ?>">
                <i class="fas fa-list-alt me-2"></i>
                Quản lý bài đăng
                <?php
                // Get post count for current user
                try {
                    $postCount = $db->count('BaiDang', 'NguoiDangID = :user_id', ['user_id' => $currentUser['ID']]);
                    if ($postCount > 0) {
                        echo '<span class="badge bg-primary rounded-pill float-end">' . $postCount . '</span>';
                    }
                } catch (Exception $e) {
                    // Silent fail
                }
                ?>
            </a>
            
            <a href="/seller/posts/create" class="list-group-item list-group-item-action <?= isActive('/seller/posts/create') ? 'active' : '' ?>">
                <i class="fas fa-plus-circle me-2"></i>
                Tạo bài đăng mới
            </a>
            
            <a href="/seller/contacts" class="list-group-item list-group-item-action <?= isActive('/seller/contacts') ? 'active' : '' ?>">
                <i class="fas fa-envelope me-2"></i>
                Liên hệ
                <?php
                // Get pending contact count
                try {
                    $contact = new \Tro365\Contact();
                    $pendingCount = $contact->count(['landlord_id' => $currentUser['ID'], 'status' => 'pending']);
                    if ($pendingCount > 0) {
                        echo '<span class="badge bg-warning rounded-pill float-end">' . $pendingCount . '</span>';
                    }
                } catch (Exception $e) {
                    // Silent fail
                }
                ?>
            </a>
            
            <a href="/seller/transactions" class="list-group-item list-group-item-action <?= isActive('/seller/transactions') ? 'active' : '' ?>">
                <i class="fas fa-handshake me-2"></i>
                Giao dịch
                <?php
                // Get transaction count
                try {
                    $transaction = new \Tro365\Transaction();
                    $transactionCount = $transaction->count(['landlord_id' => $currentUser['ID']]);
                    if ($transactionCount > 0) {
                        echo '<span class="badge bg-success rounded-pill float-end">' . $transactionCount . '</span>';
                    }
                } catch (Exception $e) {
                    // Silent fail
                }
                ?>
            </a>
            
            <a href="/seller/stats" class="list-group-item list-group-item-action <?= isActive('/seller/stats') ? 'active' : '' ?>">
                <i class="fas fa-chart-bar me-2"></i>
                Thống kê
            </a>
        </div>
        
        <div class="card-footer">
            <div class="d-grid gap-2">
                <a href="/seller/posts/create" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-2"></i>
                    Đăng bài mới
                </a>
                <a href="/profile" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-user me-2"></i>
                    Hồ sơ cá nhân
                </a>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats Card -->
    <div class="card mt-3">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-chart-pie me-2"></i>
                Thống kê nhanh
            </h6>
        </div>
        <div class="card-body">
            <?php
            try {
                // Get quick stats
                $totalPosts = $db->count('BaiDang', 'NguoiDangID = :user_id', ['user_id' => $currentUser['ID']]);
                $approvedPosts = $db->count('BaiDang', 'NguoiDangID = :user_id AND TrangThai = 1', ['user_id' => $currentUser['ID']]);
                $totalViews = $db->selectOne(
                    "SELECT SUM(LuotXem) as total FROM BaiDang WHERE NguoiDangID = :user_id",
                    ['user_id' => $currentUser['ID']]
                )['total'] ?? 0;
                
                $contact = new \Tro365\Contact();
                $totalContacts = $contact->count(['landlord_id' => $currentUser['ID']]);
                
                $transaction = new \Tro365\Transaction();
                $totalTransactions = $transaction->count(['landlord_id' => $currentUser['ID']]);
            ?>
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="border-end">
                            <h5 class="text-primary mb-1"><?= $totalPosts ?></h5>
                            <small class="text-muted">Tổng bài đăng</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <h5 class="text-success mb-1"><?= $approvedPosts ?></h5>
                        <small class="text-muted">Đã duyệt</small>
                    </div>
                    <div class="col-6">
                        <div class="border-end">
                            <h5 class="text-info mb-1"><?= number_format($totalViews) ?></h5>
                            <small class="text-muted">Lượt xem</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h5 class="text-warning mb-1"><?= $totalContacts ?></h5>
                        <small class="text-muted">Liên hệ</small>
                    </div>
                </div>
                
                <?php if ($totalTransactions > 0): ?>
                    <div class="text-center mt-3 pt-3 border-top">
                        <h5 class="text-success mb-1"><?= $totalTransactions ?></h5>
                        <small class="text-muted">Giao dịch thành công</small>
                    </div>
                <?php endif; ?>
                
            <?php
            } catch (Exception $e) {
                echo '<div class="text-center text-muted">';
                echo '<i class="fas fa-exclamation-triangle"></i><br>';
                echo '<small>Không thể tải thống kê</small>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
    
    <!-- Help Card -->
    <div class="card mt-3">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-question-circle me-2"></i>
                Cần hỗ trợ?
            </h6>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Bạn cần hỗ trợ về việc đăng bài hoặc quản lý tài khoản?
            </p>
            <div class="d-grid gap-2">
                <a href="/contact" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-headset me-2"></i>
                    Liên hệ hỗ trợ
                </a>
                <a href="/help" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-book me-2"></i>
                    Hướng dẫn
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.list-group-item.active {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}

.list-group-item:hover {
    background-color: var(--bs-light);
}

.list-group-item.active:hover {
    background-color: var(--bs-primary);
}

.badge.float-end {
    margin-top: 2px;
}
</style>
