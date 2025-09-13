<?php
/**
 * Admin Footer Layout (Scripts Only - No Visual Footer)
 * Tro365 - Website thuê trọ
 */
?>

</div>
<!-- End Main Content Wrapper -->

<!-- Modern JavaScript Libraries (AssetManager) -->
<?php
$am = new \Tro365\Assets\AssetManager(app_url(''));
echo $am->renderFooter();
?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<!-- Admin Dropdown Manager -->
<script src="/assets/js/admin/dropdown.js"></script>

<!-- Admin Mobile Navigation -->
<script src="/assets/js/admin/mobile-nav.js"></script>

<!-- Common JavaScript Functions -->
<script src="/assets/js/global/common.js"></script>

<!-- Image Fallback JavaScript -->
<script src="/assets/js/global/image-fallback.js"></script>

<!-- Additional JS for specific pages -->
<?php if (isset($additionalJS)): ?>
    <?php foreach ($additionalJS as $js): ?>
        <script src="<?= $js ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Custom JS -->
<?php if (isset($customJS)): ?>
    <script><?= $customJS ?></script>
<?php endif; ?>

<script>
// Global Configuration (consistent with client)
window.Tro365Config = {
    csrfToken: '<?= $_SESSION['csrf_token'] ?? '' ?>',
    userRole: <?= isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'null' ?>,
    appUrl: '<?= app_url() ?>',
    isLoggedIn: <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>
};

// Set CSRF token for all AJAX requests
if (window.jQuery) {
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                xhr.setRequestHeader("X-CSRF-Token", window.Tro365Config.csrfToken);
            }
        }
    });
}

// Common admin functions
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function formatCurrency(amount) {
    // Delegate to TroCurrency if available, fallback to Intl
    if (window.TroCurrency && typeof window.TroCurrency.formatCurrency === 'function') {
        return window.TroCurrency.formatCurrency(amount);
    }
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function formatNumber(number) {
    // Delegate to TroCurrency if available, fallback to Intl
    if (window.TroCurrency && typeof window.TroCurrency.formatNumber === 'function') {
        return window.TroCurrency.formatNumber(number);
    }
    return new Intl.NumberFormat('vi-VN').format(number);
}

// Loading state management
function showLoading(element) {
    if (element) {
        element.classList.add('loading');
        element.disabled = true;
    }
}

function hideLoading(element) {
    if (element) {
        element.classList.remove('loading');
        element.disabled = false;
    }
}

// Confirm dialog
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Auto-dismiss dismissible alerts only (notification alerts, not content alerts)
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert.alert-dismissible');
    alerts.forEach(function(alert) {
        if (bootstrap.Alert.getOrCreateInstance) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }
    });
}, 5000);

// Note: Dropdown and Bootstrap components are now handled by Admin Dropdown Manager
// loaded from /assets/js/admin/dropdown.js

// Initialize admin-specific components on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables if present with safe loading
    setTimeout(function() {
        if (typeof $ !== 'undefined' && typeof $.fn !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
            $('.data-table').each(function() {
                if (!$.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable({
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
                        },
                        responsive: true,
                        pageLength: 25,
                        order: [[0, 'desc']]
                    });
                }
            });
            console.log('✅ DataTables initialized successfully');
        } else {
            // Only log warning if there are actually data-table elements on the page
            if (document.querySelector('.data-table')) {
                console.warn('⚠️ DataTables not available but .data-table elements found');
            }
        }
    }, 500); // Wait 500ms for all scripts to load

    // Footer stats removed
});

// Footer statistics functions removed

// Set current user role for session refresh
<?php if (isset($_SESSION['user_role'])): ?>
window.currentUserRole = <?= $_SESSION['user_role'] ?>;
<?php endif; ?>
</script>

<?php if (isset($_SESSION['user_role'])): ?>
<!-- Session Auto-Refresh for admin users -->
<script src="/assets/js/global/session-refresh.js"></script>
<?php endif; ?>

<?php
// Render Debug Panel for admin pages (skip for AJAX requests)
if (isDebugModeEnabled() && !defined('DISABLE_DEBUG_PANEL')) {
    $debugManager = \Tro365\Services\DebugManager::getInstance();
    echo $debugManager->renderDebugPanel();
}
?>

</body>
</html>
