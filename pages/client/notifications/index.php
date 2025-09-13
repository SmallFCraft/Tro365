<?php
/**
 * Notifications Page
 * Tro365 - Website thuê trọ
 */

// Performance optimization includes
require_once __DIR__ . '/../../../includes/performance/optimization.php';

use Tro365\Services\PerformanceOptimizationService;

// Initialize performance service
$perfService = PerformanceOptimizationService::getInstance();

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

/* Performance optimization: Loading states with Glass Morphism */
.btn.loading {
    position: relative;
    color: transparent !important;
    pointer-events: none;
}

.btn.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 16px;
    height: 16px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    color: var(--primary-color);
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}

/* Performance optimization: Smooth transitions for optimistic updates */
.notification-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.notification-item.removing {
    opacity: 0;
    transform: translateX(-100%);
    height: 0;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

/* Performance optimization: Enhanced loading spinner */
.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-3xl);
    gap: var(--spacing-md);
    background: var(--glass-bg);
    backdrop-filter: var(--glass-blur);
    border: var(--glass-border);
    border-radius: var(--border-radius-lg);
    color: var(--text-muted);
}

.loading-spinner i {
    font-size: var(--font-size-2xl);
    color: var(--primary-color);
    animation: spin 1s linear infinite;
}

/* Performance optimization: Optimistic UI feedback */
.notification-item.optimistic-update {
    opacity: 0.7;
    pointer-events: none;
}

.notification-item.optimistic-success {
    background: linear-gradient(135deg,
        rgba(34, 197, 94, 0.1) 0%,
        rgba(34, 197, 94, 0.05) 100%);
    border-color: rgba(34, 197, 94, 0.2);
}

.notification-item.optimistic-error {
    background: linear-gradient(135deg,
        rgba(239, 68, 68, 0.1) 0%,
        rgba(239, 68, 68, 0.05) 100%);
    border-color: rgba(239, 68, 68, 0.2);
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Performance optimization: Improved mobile responsiveness */
@media (max-width: 768px) {
    .btn.loading::after {
        width: 14px;
        height: 14px;
        border-width: 1.5px;
    }

    .loading-spinner {
        padding: var(--spacing-xl);
    }

    .loading-spinner i {
        font-size: var(--font-size-xl);
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

    // Performance optimization: State management
    let isLoading = false;
    let requestCache = new Map();
    let cacheTimeout = 30000; // 30 seconds cache

    // Performance optimization: Debouncing utility
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Performance optimization: Loading state management
    function setLoadingState(element, loading) {
        if (loading) {
            element.disabled = true;
            element.classList.add('loading');
            const originalText = element.textContent;
            element.dataset.originalText = originalText;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        } else {
            element.disabled = false;
            element.classList.remove('loading');
            element.textContent = element.dataset.originalText || element.textContent;
        }
    }
    
    // Performance optimization: Cached API request
    async function makeApiRequest(url, options = {}) {
        const cacheKey = `${url}_${JSON.stringify(options)}`;
        const cached = requestCache.get(cacheKey);

        if (cached && Date.now() - cached.timestamp < cacheTimeout) {
            return cached.data;
        }

        const response = await fetch(url, options);
        const result = await response.json();

        // Cache successful responses
        if (response.ok) {
            requestCache.set(cacheKey, {
                data: result,
                timestamp: Date.now()
            });
        }

        return result;
    }

    // Performance optimization: Clear cache
    function clearCache() {
        requestCache.clear();
    }

    // Load notifications with performance optimizations
    async function loadNotifications(filter = 'all', page = 1, skipCache = false) {
        // Prevent multiple simultaneous requests
        if (isLoading) return;

        try {
            isLoading = true;

            // Show loading state with minimal DOM manipulation
            showLoadingState();

            const params = new URLSearchParams({
                limit: 20,
                offset: (page - 1) * 20
            });

            if (filter === 'unread') {
                params.append('unread_only', 'true');
            }

            // Clear cache if explicitly requested
            if (skipCache) {
                clearCache();
            }

            const result = await makeApiRequest(`/api/notifications?${params}`);

            if (result.success) {
                // Use requestAnimationFrame for smooth DOM updates
                requestAnimationFrame(() => {
                    renderNotifications(result.data.notifications || []);
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            showErrorState(filter, page);
        } finally {
            isLoading = false;
        }
    }

    // Performance optimization: Minimal loading state
    function showLoadingState() {
        if (notificationsList.children.length === 0) {
            notificationsList.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Đang tải thông báo...</span>
                </div>
            `;
        }
    }

    // Performance optimization: Error state
    function showErrorState(filter, page) {
        notificationsList.innerHTML = `
            <div class="notification-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Không thể tải thông báo</p>
                <button onclick="loadNotifications('${filter}', ${page}, true)" class="btn btn-primary">Thử lại</button>
            </div>
        `;
    }
    
    // Performance optimization: Efficient DOM rendering with DocumentFragment
    function renderNotifications(notifications) {
        // Clear existing content efficiently
        while (notificationsList.firstChild) {
            notificationsList.removeChild(notificationsList.firstChild);
        }

        if (notifications.length === 0) {
            const emptyState = document.createElement('div');
            emptyState.className = 'notification-empty';
            emptyState.innerHTML = `
                <i class="fas fa-bell-slash"></i>
                <h3>Không có thông báo</h3>
                <p>Bạn chưa có thông báo nào.</p>
            `;
            notificationsList.appendChild(emptyState);
            return;
        }

        // Use DocumentFragment for efficient DOM manipulation
        const fragment = document.createDocumentFragment();

        notifications.forEach(notification => {
            const notificationElement = createNotificationElement(notification);
            fragment.appendChild(notificationElement);
        });

        // Single DOM append operation
        notificationsList.appendChild(fragment);

        // Re-observe images for lazy loading
        if (window.lazyImageLoader) {
            const images = notificationsList.querySelectorAll('img[data-src], img[loading="lazy"]');
            images.forEach(img => window.lazyImageLoader.observe(img));
        }
    }

    // Performance optimization: Create notification element efficiently
    function createNotificationElement(notification) {
        const div = document.createElement('div');
        div.className = `notification-item ${notification.read ? '' : 'unread'}`;
        div.dataset.id = notification.ID;

        // Build content efficiently
        const header = document.createElement('div');
        header.className = 'notification-header';

        const title = document.createElement('h5');
        title.className = 'notification-title';
        title.textContent = notification.title;

        const typeSpan = document.createElement('span');
        typeSpan.className = `notification-type type-${notification.type}`;
        typeSpan.innerHTML = getNotificationTypeIcon(notification.type);

        header.appendChild(title);
        header.appendChild(typeSpan);

        const message = document.createElement('div');
        message.className = 'notification-message';
        message.textContent = notification.message;

        const footer = document.createElement('div');
        footer.className = 'notification-footer';

        const time = document.createElement('span');
        time.className = 'notification-time';
        time.textContent = notification.time;

        const actions = document.createElement('div');
        actions.className = 'notification-actions';

        if (!notification.read) {
            const markReadBtn = document.createElement('button');
            markReadBtn.className = 'btn btn-sm btn-outline-primary';
            markReadBtn.textContent = 'Đánh dấu đã đọc';
            markReadBtn.addEventListener('click', () => markAsReadOptimized(notification.ID, div));
            actions.appendChild(markReadBtn);
        }

        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-sm btn-outline-danger';
        deleteBtn.textContent = 'Xóa';
        deleteBtn.addEventListener('click', () => deleteNotificationOptimized(notification.ID, div));
        actions.appendChild(deleteBtn);

        footer.appendChild(time);
        footer.appendChild(actions);

        div.appendChild(header);
        div.appendChild(message);
        div.appendChild(footer);

        return div;
    }
    
    // Performance optimization: Debounced filter tabs
    const debouncedFilterChange = debounce((filter) => {
        currentFilter = filter;
        currentPage = 1;
        clearCache(); // Clear cache when filter changes
        loadNotifications(currentFilter, currentPage);
    }, 150);

    // Filter tabs with debouncing
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Prevent multiple rapid clicks
            if (isLoading) return;

            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            debouncedFilterChange(this.dataset.filter);
        });
    });

    // Performance optimization: Mark all as read with loading state
    markAllAsReadBtn.addEventListener('click', async function() {
        // Prevent multiple clicks
        if (isLoading || this.disabled) return;

        try {
            setLoadingState(this, true);

            const response = await fetch('/api/notifications', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' }
            });

            if (response.ok) {
                // Optimistic UI update: mark all as read immediately
                const unreadItems = notificationsList.querySelectorAll('.notification-item.unread');
                unreadItems.forEach(item => {
                    item.classList.remove('unread');
                    const markReadBtn = item.querySelector('.btn-outline-primary');
                    if (markReadBtn) {
                        markReadBtn.remove();
                    }
                });

                // Clear cache and reload for consistency
                clearCache();
                loadNotifications(currentFilter, currentPage);

                // Update navigation badge
                if (window.modernNav) {
                    window.modernNav.loadNotifications();
                }
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
            // Reload on error to restore correct state
            loadNotifications(currentFilter, currentPage, true);
        } finally {
            setLoadingState(this, false);
        }
    });

    // Performance optimization: Debounced refresh
    const debouncedRefresh = debounce(() => {
        clearCache();
        loadNotifications(currentFilter, currentPage, true);
    }, 300);

    // Refresh with debouncing
    refreshBtn.addEventListener('click', function() {
        if (isLoading) return;
        debouncedRefresh();
    });
    
    // Performance optimization: Optimized mark as read with optimistic UI
    async function markAsReadOptimized(id, element) {
        try {
            // Optimistic UI update
            element.classList.remove('unread');
            const markReadBtn = element.querySelector('.btn-outline-primary');
            if (markReadBtn) {
                setLoadingState(markReadBtn, true);
                markReadBtn.style.display = 'none';
            }

            const response = await fetch(`/api/notifications/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' }
            });

            if (response.ok) {
                // Remove button permanently on success
                if (markReadBtn) {
                    markReadBtn.remove();
                }

                // Clear cache and update navigation
                clearCache();
                if (window.modernNav) {
                    window.modernNav.loadNotifications();
                }
            } else {
                // Revert optimistic update on failure
                element.classList.add('unread');
                if (markReadBtn) {
                    markReadBtn.style.display = '';
                    setLoadingState(markReadBtn, false);
                }
            }
        } catch (error) {
            console.error('Error marking as read:', error);
            // Revert optimistic update on error
            element.classList.add('unread');
            if (markReadBtn) {
                markReadBtn.style.display = '';
                setLoadingState(markReadBtn, false);
            }
        }
    }

    // Performance optimization: Optimized delete with optimistic UI
    async function deleteNotificationOptimized(id, element) {
        if (!confirm('Bạn có chắc muốn xóa thông báo này?')) return;

        try {
            // Optimistic UI update: fade out element
            element.style.opacity = '0.5';
            element.style.pointerEvents = 'none';

            const response = await fetch(`/api/notifications/${id}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                // Smooth removal animation
                element.style.transition = 'all 0.3s ease';
                element.style.transform = 'translateX(-100%)';
                element.style.height = '0';
                element.style.margin = '0';
                element.style.padding = '0';

                setTimeout(() => {
                    if (element.parentNode) {
                        element.parentNode.removeChild(element);
                    }
                }, 300);

                // Clear cache and update navigation
                clearCache();
                if (window.modernNav) {
                    window.modernNav.loadNotifications();
                }
            } else {
                // Revert optimistic update on failure
                element.style.opacity = '';
                element.style.pointerEvents = '';
            }
        } catch (error) {
            console.error('Error deleting notification:', error);
            // Revert optimistic update on error
            element.style.opacity = '';
            element.style.pointerEvents = '';
        }
    }

    // Backward compatibility: Keep global functions for inline onclick handlers
    window.markAsRead = async function(id) {
        const element = document.querySelector(`[data-id="${id}"]`);
        if (element) {
            await markAsReadOptimized(id, element);
        }
    };

    window.deleteNotification = async function(id) {
        const element = document.querySelector(`[data-id="${id}"]`);
        if (element) {
            await deleteNotificationOptimized(id, element);
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

    // Performance optimization: Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        clearCache();
        if (window.lazyImageLoader) {
            window.lazyImageLoader.disconnect();
        }
    });

    // Performance optimization: Intersection Observer for virtual scrolling (future enhancement)
    let scrollObserver;
    if ('IntersectionObserver' in window) {
        scrollObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.target.classList.contains('load-more-trigger')) {
                    // Future: Implement pagination loading
                    console.log('Load more notifications');
                }
            });
        }, {
            rootMargin: '100px'
        });
    }

    // Initial load with performance optimization
    requestAnimationFrame(() => {
        loadNotifications();
    });
});
</script>

<?php include __DIR__ . '/../../../includes/layouts/client/footer.php'; ?>
