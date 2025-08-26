<?php
/**
 * Maintenance Mode Middleware
 * Tro365 - Website thuê trọ
 */

require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/auth.php';

use Tro365\Core\Auth;

/**
 * Check if maintenance mode is enabled and redirect non-admin users
 */
function checkMaintenanceMode()
{
    // Skip maintenance check for admin pages and API endpoints
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    $skipPaths = [
        '/admin/',
        '/api/',
        '/logout',
        '/maintenance'
    ];
    
    foreach ($skipPaths as $skipPath) {
        if (strpos($currentPath, $skipPath) !== false) {
            return; // Skip maintenance check
        }
    }
    
    // Check if maintenance mode is enabled
    if (isMaintenanceModeEnabled()) {
        $auth = new Auth();
        
        // Allow admin users to access the site
        if ($auth->isLoggedIn() && $auth->hasRole(ROLE_ADMIN)) {
            return; // Admin can access
        }
        
        // Redirect non-admin users to maintenance page
        showMaintenancePage();
        exit;
    }
}

/**
 * Show maintenance page
 */
function showMaintenancePage()
{
    http_response_code(503);
    
    $websiteName = getWebsiteName();
    $contactEmail = getCompanyInfo('email_admin', 'admin@tro365.com');
    $hotline = getCompanyInfo('sdt_hotline', '1900-xxxx');
    
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bảo trì hệ thống - <?= $websiteName ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .maintenance-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                padding: 3rem;
                text-align: center;
                max-width: 600px;
                margin: 2rem;
            }
            .maintenance-icon {
                font-size: 4rem;
                color: #667eea;
                margin-bottom: 1.5rem;
                animation: pulse 2s infinite;
            }
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            .maintenance-title {
                color: #333;
                font-weight: 700;
                margin-bottom: 1rem;
            }
            .maintenance-text {
                color: #666;
                font-size: 1.1rem;
                line-height: 1.6;
                margin-bottom: 2rem;
            }
            .contact-info {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 1.5rem;
                margin-top: 2rem;
            }
            .contact-item {
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 0.5rem;
            }
            .contact-item i {
                margin-right: 0.5rem;
                color: #667eea;
                width: 20px;
            }
            .btn-home {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                border-radius: 50px;
                padding: 12px 30px;
                color: white;
                font-weight: 600;
                text-decoration: none;
                display: inline-block;
                margin-top: 1rem;
                transition: transform 0.3s ease;
            }
            .btn-home:hover {
                transform: translateY(-2px);
                color: white;
            }
        </style>
    </head>
    <body>
        <div class="maintenance-container">
            <div class="maintenance-icon">
                <i class="fas fa-tools"></i>
            </div>
            
            <h1 class="maintenance-title">Hệ thống đang bảo trì</h1>
            
            <p class="maintenance-text">
                Chúng tôi đang thực hiện bảo trì hệ thống để mang đến trải nghiệm tốt hơn cho bạn. 
                Vui lòng quay lại sau ít phút.
            </p>
            
            <div class="contact-info">
                <h5 class="mb-3">
                    <i class="fas fa-headset me-2"></i>
                    Cần hỗ trợ khẩn cấp?
                </h5>
                
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>Email: <strong><?= $contactEmail ?></strong></span>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span>Hotline: <strong><?= $hotline ?></strong></span>
                </div>
            </div>
            
            <a href="javascript:location.reload()" class="btn-home">
                <i class="fas fa-sync-alt me-2"></i>
                Thử lại
            </a>
        </div>
        
        <script>
            // Auto refresh every 30 seconds
            setTimeout(function() {
                location.reload();
            }, 30000);
        </script>
    </body>
    </html>
    <?php
}
?>
