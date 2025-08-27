<?php
/**
 * Admin Settings Page
 * Tro365 - Website thuê trọ
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/controllers/SettingsController.php';

use Tro365\Controllers\SettingsController;

// Initialize controller
$settingsController = new SettingsController();
$settingsController->checkAdminAccess();

$success = '';
$error = '';

// Redirect AJAX requests to handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // AJAX requests should go directly to /admin/ajax/settings-handler.php
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please use /admin/ajax/settings-handler.php for AJAX requests']);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $isAutoSave = isset($_POST['auto_save']);

        if ($isAutoSave) {
            $settingsController->autoSave($_POST);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Đã lưu tự động']);
            exit;
        }

        // Handle regular form submission
        if (isset($_POST['website_settings'])) {
            $settingsController->updateWebsiteSettings($_POST);
        }

        if (isset($_POST['system_settings'])) {
            $settingsController->updateSystemSettings($_POST);
        }

        if (isset($_POST['email_settings'])) {
            $settingsController->updateEmailSettings($_POST);
        }

        if (isset($_POST['seo_settings'])) {
            $settingsController->updateSeoSettings($_POST);
        }

        $success = 'Cài đặt đã được cập nhật thành công!';

    } catch (Exception $e) {
        $error = 'Có lỗi xảy ra: ' . $e->getMessage();

        if ($isAutoSave) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
    }
}

// Get current settings
$allSettings = $settingsController->getAllSettings();
$settings = $allSettings;
$additionalSettings = $allSettings;

// Ensure app_debug setting exists
if (!isset($additionalSettings['app_debug'])) {
    $additionalSettings['app_debug'] = '1'; // Default to enabled in development
}

// For backward compatibility
$config = new \Tro365\Core\Config();
$db = new \Tro365\Core\Database();

$pageTitle = 'Cài đặt hệ thống';
include __DIR__ . '/../../includes/layouts/admin/header.php';
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
                        <i class="fas fa-cog me-1"></i>
                        Cài đặt hệ thống
                    </li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">
                            <i class="fas fa-cog me-3"></i>
                            Cài đặt hệ thống
                        </h1>
                        <p class="text-muted mb-0">Quản lý cấu hình và thiết lập toàn bộ hệ thống</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-warning" onclick="Tro365Settings.clearCache()" title="Xóa cache hệ thống và log files">
                            <i class="fas fa-trash me-2"></i>Xóa Cache & Logs
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="exportSettings()">
                            <i class="fas fa-download me-2"></i>Xuất cấu hình
                        </button>
                        <button type="submit" form="settingsForm" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Lưu cài đặt
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Settings Form -->
            <form id="settingsForm" method="POST" class="needs-validation" novalidate>
                <!-- Settings Tabs -->
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#website-tab" type="button">
                                    <i class="fas fa-globe me-1"></i>
                                    Website
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#system-tab" type="button">
                                    <i class="fas fa-cogs me-1"></i>
                                    Hệ thống
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#email-tab" type="button">
                                    <i class="fas fa-envelope me-1"></i>
                                    Email
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#seo-tab" type="button">
                                    <i class="fas fa-search me-1"></i>
                                    SEO
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#advanced-tab" type="button">
                                    <i class="fas fa-tools me-1"></i>
                                    Nâng cao
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        <div class="tab-content">
                            <?php include __DIR__ . '/../../includes/layouts/admin/settings/website-tab.php'; ?>
                            <?php include __DIR__ . '/../../includes/layouts/admin/settings/system-tab.php'; ?>
                            <?php include __DIR__ . '/../../includes/layouts/admin/settings/email-tab.php'; ?>
                            <?php include __DIR__ . '/../../includes/layouts/admin/settings/seo-tab.php'; ?>
                            <?php include __DIR__ . '/../../includes/layouts/admin/settings/advanced-tab.php'; ?>






                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layouts/admin/footer.php'; ?>

<!-- Additional CSS -->
<style>
.nav-tabs .nav-link {
    border-radius: 0.5rem 0.5rem 0 0;
    margin-right: 0.25rem;
}

.nav-tabs .nav-link.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.form-control:focus, .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.auto-save-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
}
</style>

<!-- Additional JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Pass system data to JavaScript
window.systemData = {
    php_version: '<?= PHP_VERSION ?>',
    server: '<?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?>',
    upload_max_size: '<?= ini_get('upload_max_filesize') ?>',
    memory_limit: '<?= ini_get('memory_limit') ?>'
};
</script>

<!-- Edit Version Description Modal -->
<div class="modal fade" id="editVersionModal" tabindex="-1" aria-labelledby="editVersionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editVersionModalLabel">
                    <i class="fas fa-edit me-2"></i>
                    Chỉnh sửa mô tả phiên bản
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editVersionForm">
                    <div class="mb-3">
                        <label for="editVersionNumber" class="form-label">Phiên bản</label>
                        <input type="text" class="form-control" id="editVersionNumber" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="editVersionDescription" class="form-label">Mô tả</label>
                        <textarea class="form-control" id="editVersionDescription" rows="3" maxlength="200" required></textarea>
                        <div class="form-text">Tối đa 200 ký tự</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Hủy
                </button>
                <button type="button" class="btn btn-primary save-version-btn">
                    <i class="fas fa-save me-1"></i>
                    Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/admin/settings.js"></script>

<script>
// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tabs manually if needed
    const triggerTabList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tab"]'));
    triggerTabList.forEach(function (triggerEl) {
        const tabTrigger = new bootstrap.Tab(triggerEl);

        triggerEl.addEventListener('click', function (event) {
            event.preventDefault();
            tabTrigger.show();
        });
    });

    // Tro365Settings is already initialized in settings.js
    // Just initialize basic functionality here
    initBasicFunctionality();
});

// Fallback functionality if settings.js doesn't load
function initBasicFunctionality() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    console.log('Basic settings functionality initialized (fallback mode)');
}

// Additional settings functions
function exportSettings() {
    // Create JSON export of current settings
    const settings = {
        website: {
            name: document.querySelector('[name="website_name"]')?.value,
            description: document.querySelector('[name="website_description"]')?.value,
            keywords: document.querySelector('[name="website_keywords"]')?.value,
            logo: document.querySelector('[name="website_logo"]')?.value
        },
        contact: {
            email: document.querySelector('[name="contact_email"]')?.value,
            phone: document.querySelector('[name="contact_phone"]')?.value,
            address: document.querySelector('[name="contact_address"]')?.value
        },
        social: {
            facebook: document.querySelector('[name="facebook_url"]')?.value,
            youtube: document.querySelector('[name="youtube_url"]')?.value,
            zalo: document.querySelector('[name="zalo_url"]')?.value
        },
        exportTime: new Date().toISOString()
    };

    const dataStr = JSON.stringify(settings, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});

    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = 'tro365-settings-' + new Date().toISOString().split('T')[0] + '.json';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function refreshSettings() {
    const refreshBtn = document.querySelector('[onclick="refreshSettings()"]');
    if (refreshBtn) {
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i>Đang làm mới...';
        refreshBtn.disabled = true;
    }

    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

function backupSettings() {
    if (confirm('🛡️ Tạo bản sao lưu cài đặt hiện tại?')) {
        // Create backup
        const backupData = {
            timestamp: new Date().toISOString(),
            settings: {},
            version: '<?= getAppVersion() ?>'
        };

        // Collect all form data
        const form = document.getElementById('settingsForm');
        const formData = new FormData(form);

        for (let [key, value] of formData.entries()) {
            backupData.settings[key] = value;
        }

        const dataStr = JSON.stringify(backupData, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});

        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = 'tro365-backup-' + new Date().toISOString().split('T')[0] + '.json';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Show success message
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
        alert.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            Đã tạo bản sao lưu thành công!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}

// Remove any old drafts on load to disable draft feature completely
document.addEventListener('DOMContentLoaded', function() {
    try {
        localStorage.removeItem('tro365_settings_draft');
    } catch (e) {}
});
</script>

</body>
</html>
