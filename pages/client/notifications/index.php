<?php
/**
 * Notifications Page
 * Tro365 - Website thuê trọ
 */

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/login');
    exit;
}

$pageTitle = 'Thông báo';
$pageDescription = 'Xem tất cả thông báo của bạn';

// Include header
include __DIR__ . '/../../../includes/layouts/client/header.php';
?>

<div class="notifications-page">
    <div class="container">
        <div class="page-header">
            <div class="breadcrumb">
                <a href="/" class="breadcrumb-item">
                    <i class="fas fa-home"></i>
                    Trang chủ
                </a>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-item active">Thông báo</span>
            </div>
            
            <h1 class="page-title">
                <i class="fas fa-bell"></i>
                Thông báo
            </h1>
            
            <div class="page-actions">
                <button id="markAllAsRead" class="btn btn-outline-primary">
                    <i class="fas fa-check-double"></i>
                    Đánh dấu tất cả đã đọc
                </button>
                <button id="refreshNotifications" class="btn btn-outline-secondary">
                    <i class="fas fa-sync-alt"></i>
                    Làm mới
                </button>
            </div>
        </div>

        <div class="notifications-container">
            <div class="notifications-filters">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all">
                        <i class="fas fa-list"></i>
                        Tất cả
                    </button>
                    <button class="filter-tab" data-filter="unread">
                        <i class="fas fa-envelope"></i>
                        Chưa đọc
                    </button>
                    <button class="filter-tab" data-filter="important">
                        <i class="fas fa-exclamation-circle"></i>
                        Quan trọng
                    </button>
                </div>
            </div>

            <div class="notifications-list" id="notificationsList">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Đang tải thông báo...</span>
                </div>
            </div>

            <div class="notifications-pagination" id="notificationsPagination">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.notifications-page {
    min-height: calc(100vh - 200px);
    padding: var(--spacing-xl) 0;
}

.page-header {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-2xl);
    padding-bottom: var(--spacing-lg);
    border-bottom: 1px solid var(--border-color);
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    font-size: var(--font-size-sm);
    color: var(--text-muted);
}

.breadcrumb-item {
    color: var(--text-muted);
    text-decoration: none;
    transition: color var(--transition-fast);
}

.breadcrumb-item:hover {
    color: var(--primary-color);
}

.breadcrumb-item.active {
    color: var(--text-primary);
    font-weight: 500;
}

.breadcrumb-separator {
    color: var(--text-muted);
}

.page-title {
    font-size: var(--font-size-3xl);
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.page-title i {
    color: var(--primary-color);
}

.page-actions {
    display: flex;
    gap: var(--spacing-md);
    flex-wrap: wrap;
}

.notifications-filters {
    margin-bottom: var(--spacing-xl);
}

.filter-tabs {
    display: flex;
    gap: var(--spacing-xs);
    border-bottom: 1px solid var(--border-color);
}

.filter-tab {
    padding: var(--spacing-md) var(--spacing-lg);
    border: none;
    background: transparent;
    color: var(--text-muted);
    font-weight: 500;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.filter-tab:hover {
    color: var(--primary-color);
    background: var(--bg-secondary);
}

.filter-tab.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.notifications-list {
    min-height: 400px;
}

.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-3xl);
    color: var(--text-muted);
    gap: var(--spacing-md);
}

.loading-spinner i {
    font-size: 2rem;
    color: var(--primary-color);
}

.notification-item {
    padding: var(--spacing-lg);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-bottom: var(--spacing-md);
    background: var(--bg-primary);
    transition: all var(--transition-fast);
}

.notification-item:hover {
    border-color: var(--primary-color);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.notification-item.unread {
    border-left: 4px solid var(--primary-color);
    background: rgba(13, 110, 253, 0.02);
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-sm);
}

.notification-title {
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
    flex: 1;
}

.notification-type {
    flex-shrink: 0;
    margin-left: var(--spacing-sm);
}

.notification-message {
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: var(--spacing-md);
}

.notification-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-time {
    font-size: var(--font-size-sm);
    color: var(--text-muted);
}

.notification-actions {
    display: flex;
    gap: var(--spacing-xs);
}

.btn-sm {
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: var(--font-size-sm);
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        text-align: center;
    }
    
    .page-actions {
        justify-content: center;
    }
    
    .filter-tabs {
        justify-content: center;
    }
    
    .notification-header {
        flex-direction: column;
        gap: var(--spacing-sm);
    }
    
    .notification-footer {
        flex-direction: column;
        gap: var(--spacing-sm);
        align-items: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationsList = document.getElementById('notificationsList');
    const filterTabs = document.querySelectorAll('.filter-tab');
    const markAllAsReadBtn = document.getElementById('markAllAsRead');
    const refreshBtn = document.getElementById('refreshNotifications');
    
    let currentFilter = 'all';
    let currentPage = 1;
    
    // Load notifications
    async function loadNotifications(filter = 'all', page = 1) {
        try {
            notificationsList.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Đang tải thông báo...</span>
                </div>
            `;
            
            const params = new URLSearchParams({
                limit: 20,
                offset: (page - 1) * 20
            });
            
            if (filter === 'unread') {
                params.append('unread_only', 'true');
            }
            
            const response = await fetch(`/router/api/notifications.php?${params}`);
            const result = await response.json();
            
            if (result.success) {
                renderNotifications(result.data.notifications || []);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            notificationsList.innerHTML = `
                <div class="notification-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Không thể tải thông báo</p>
                    <button onclick="loadNotifications('${filter}', ${page})" class="btn btn-primary">Thử lại</button>
                </div>
            `;
        }
    }
    
    // Render notifications
    function renderNotifications(notifications) {
        if (notifications.length === 0) {
            notificationsList.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <h3>Không có thông báo</h3>
                    <p>Bạn chưa có thông báo nào.</p>
                </div>
            `;
            return;
        }
        
        notificationsList.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.read ? '' : 'unread'}" data-id="${notification.ID}">
                <div class="notification-header">
                    <h5 class="notification-title">${escapeHtml(notification.title)}</h5>
                    <span class="notification-type type-${notification.type}">
                        ${getNotificationTypeIcon(notification.type)}
                    </span>
                </div>
                <div class="notification-message">${escapeHtml(notification.message)}</div>
                <div class="notification-footer">
                    <span class="notification-time">${notification.time}</span>
                    <div class="notification-actions">
                        ${!notification.read ? `<button class="btn btn-sm btn-outline-primary" onclick="markAsRead(${notification.ID})">Đánh dấu đã đọc</button>` : ''}
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteNotification(${notification.ID})">Xóa</button>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    // Filter tabs
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            currentPage = 1;
            loadNotifications(currentFilter, currentPage);
        });
    });
    
    // Mark all as read
    markAllAsReadBtn.addEventListener('click', async function() {
        try {
            const response = await fetch('/router/api/notifications.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (response.ok) {
                loadNotifications(currentFilter, currentPage);
                // Update navigation badge
                if (window.modernNav) {
                    window.modernNav.loadNotifications();
                }
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    });
    
    // Refresh
    refreshBtn.addEventListener('click', function() {
        loadNotifications(currentFilter, currentPage);
    });
    
    // Helper functions
    window.markAsRead = async function(id) {
        try {
            const response = await fetch(`/router/api/notifications.php/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' }
            });
            
            if (response.ok) {
                loadNotifications(currentFilter, currentPage);
                if (window.modernNav) {
                    window.modernNav.loadNotifications();
                }
            }
        } catch (error) {
            console.error('Error marking as read:', error);
        }
    };
    
    window.deleteNotification = async function(id) {
        if (!confirm('Bạn có chắc muốn xóa thông báo này?')) return;
        
        try {
            const response = await fetch(`/router/api/notifications.php/${id}`, {
                method: 'DELETE'
            });
            
            if (response.ok) {
                loadNotifications(currentFilter, currentPage);
                if (window.modernNav) {
                    window.modernNav.loadNotifications();
                }
            }
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    };
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function getNotificationTypeIcon(type) {
        switch (type) {
            case 1: return '<i class="fas fa-info-circle text-info"></i>';
            case 2: return '<i class="fas fa-exclamation-circle text-warning"></i>';
            case 3: return '<i class="fas fa-exclamation-triangle text-danger"></i>';
            default: return '<i class="fas fa-bell text-primary"></i>';
        }
    }
    
    // Initial load
    loadNotifications();
});
</script>

<?php include __DIR__ . '/../../../includes/layouts/client/footer.php'; ?>
