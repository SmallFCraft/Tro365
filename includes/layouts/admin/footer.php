<?php
/**
 * Admin Footer Layout
 * Tro365 - Website thuê trọ
 */
?>

</div>
<!-- End Main Content Wrapper -->

<!-- Admin Footer -->
<footer class="mt-5">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <div class="d-flex align-items-center justify-content-center justify-content-lg-start">
                    <div class="me-3">
                        <i class="fas fa-shield-alt fa-2x" style="background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                    </div>
                    <div>
                        <strong class="fw-bold"><?= getWebsiteName() ?> Admin</strong>
                        <br><small class="text-muted">Phiên bản <?= getAppVersion() ?></small>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0 text-center">
                <!-- Status indicators removed -->
            </div>

            <div class="col-lg-4 col-12 text-center text-lg-end">
                <div class="mb-2">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Đăng nhập: <?= date('d/m/Y H:i', strtotime($currentUser['LanDangNhapCuoi'] ?? 'now')) ?>
                    </small>
                </div>
                <small class="text-muted">
                    &copy; <?= date('Y') ?> <?= COMPANY_NAME ?>
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- Modern JavaScript Libraries -->
<?php
require_once __DIR__ . '/../../modern-assets.php';
loadModernJS();
initModernFormValidation();
?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<!-- Admin Dropdown Manager -->
<script src="/assets/js/admin/dropdown.js"></script>

<!-- Common JavaScript Functions -->
<script src="/assets/js/common.js"></script>

<!-- Image Fallback JavaScript -->
<script src="/assets/js/image-fallback.js"></script>

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
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

function formatNumber(number) {
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
    // Initialize DataTables if present
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.data-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }

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
<script src="/assets/js/session-refresh.js"></script>
<?php endif; ?>

<?php
// Render Debug Panel for admin pages
if (isDebugModeEnabled()) {
    $debugManager = \Tro365\DebugManager::getInstance();
    echo $debugManager->renderDebugPanel();
}
?>

</body>
</html>
