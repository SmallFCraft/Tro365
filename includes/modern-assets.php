<?php
/**
 * Modern Assets Integration - Thay thế asset loading cũ
 * Tích hợp các thư viện hiện đại vào template
 */

/**
 * Load modern JavaScript libraries (delegated to AssetManager)
 */
function loadModernJS() {
    try {
        $csrf = function_exists('csrf_token') ? csrf_token() : '';
        $base = function_exists('app_url') ? app_url('') : null;
        $am = new \Tro365\Assets\AssetManager($base);
        if ($csrf) { $am->addMetaTags(['csrf' => $csrf]); }
        // Keep defaults same as before
        echo $am->renderFooter();
    } catch (\Throwable $e) {
        // Fallback to original behavior if class not found
        $baseUrl = getBaseUrl();
        $version = '1.0.0';
        echo "<!-- Modern JavaScript Libraries -->\n";
        echo "<script src=\"{$baseUrl}/assets/js/modern/http-client.js?v={$version}\"></script>\n";
        echo "<script src=\"{$baseUrl}/assets/js/modern/dom-utils.js?v={$version}\"></script>\n";
        echo "<script src=\"{$baseUrl}/assets/js/modern/form-validator.js?v={$version}\"></script>\n";
        echo "<script src=\"{$baseUrl}/assets/js/modern/app.js?v={$version}\"></script>\n";
        echo "<!-- Modern JavaScript Libraries (Local) -->\n";
        echo "<script src=\"{$baseUrl}/assets/js/vendor/alpine.min.js\" defer></script>\n";
        echo "<script src=\"{$baseUrl}/assets/js/vendor/axios.min.js\"></script>\n";
        echo "<script src=\"{$baseUrl}/assets/js/vendor/dayjs.min.js\"></script>\n";
    }
}

/**
 * Load modern CSS utilities (delegated to AssetManager)
 */
function loadModernCSS() {
    try {
        $base = function_exists('app_url') ? app_url('') : null;
        $am = new \Tro365\Assets\AssetManager($base);
        echo $am->renderHead();
    } catch (\Throwable $e) {
        // Fallback to original behavior if class not found
        $baseUrl = getBaseUrl();
        $version = '1.0.0';
        echo "<!-- Modern CSS Utilities -->\n";
        echo "<link rel=\"stylesheet\" href=\"{$baseUrl}/assets/css/modern/utilities.css?v={$version}\">\n";
    }
}

/**
 * Add modern meta tags
 */
function addModernMetaTags() {
    echo "<!-- Modern Meta Tags -->\n";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, shrink-to-fit=no\">\n";
    echo "<meta name=\"theme-color\" content=\"#667eea\">\n";
    echo "<meta name=\"apple-mobile-web-app-capable\" content=\"yes\">\n";
    echo "<meta name=\"apple-mobile-web-app-status-bar-style\" content=\"default\">\n";
    
    // CSRF Token for AJAX requests
    if (function_exists('getCsrfToken')) {
        $csrfToken = getCsrfToken();
        echo "<meta name=\"csrf-token\" content=\"{$csrfToken}\">\n";
    }
    
    // Preload critical resources
    $baseUrl = getBaseUrl();
    echo "<link rel=\"preload\" href=\"{$baseUrl}/assets/js/modern/app.js\" as=\"script\">\n";
    echo "<link rel=\"preload\" href=\"{$baseUrl}/assets/css/modern/utilities.css\" as=\"style\">\n";
    
    // DNS prefetch for CDN resources
    echo "<link rel=\"dns-prefetch\" href=\"//cdn.jsdelivr.net\">\n";
    echo "<link rel=\"preconnect\" href=\"https://cdn.jsdelivr.net\" crossorigin>\n";
}

/**
 * Initialize modern form validation
 */
function initModernFormValidation($formSelector = 'form[data-validate]') {
    echo "<script>\n";
    echo "document.addEventListener('DOMContentLoaded', function() {\n";
    echo "    const forms = document.querySelectorAll('{$formSelector}');\n";
    echo "    \n";
    echo "    forms.forEach(form => {\n";
    echo "        if (window.FormValidator) {\n";
    echo "            const validator = new FormValidator(form, {\n";
    echo "                realTimeValidation: true,\n";
    echo "                showErrors: true,\n";
    echo "                errorClass: 'is-invalid',\n";
    echo "                successClass: 'is-valid'\n";
    echo "            });\n";
    echo "            \n";
    echo "            // Add common validation rules\n";
    echo "            const emailFields = form.querySelectorAll('input[type=\"email\"]');\n";
    echo "            emailFields.forEach(field => {\n";
    echo "                validator.addRule(field.name, FormValidator.rules.email, 'Email không hợp lệ');\n";
    echo "            });\n";
    echo "            \n";
    echo "            const phoneFields = form.querySelectorAll('input[type=\"tel\"], input[name*=\"phone\"]');\n";
    echo "            phoneFields.forEach(field => {\n";
    echo "                validator.addRule(field.name, FormValidator.rules.phone, 'Số điện thoại không hợp lệ');\n";
    echo "            });\n";
    echo "            \n";
    echo "            const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');\n";
    echo "            requiredFields.forEach(field => {\n";
    echo "                validator.addRule(field.name, FormValidator.rules.required, 'Trường này là bắt buộc');\n";
    echo "            });\n";
    echo "        }\n";
    echo "    });\n";
    echo "});\n";
    echo "</script>\n";
}

/**
 * Add modern loading indicators
 */
function addModernLoadingIndicators() {
    echo "<style>\n";
    echo "/* Modern page loading indicator */\n";
    echo ".page-loading {\n";
    echo "    position: fixed;\n";
    echo "    top: 0;\n";
    echo "    left: 0;\n";
    echo "    width: 100%;\n";
    echo "    height: 4px;\n";
    echo "    background: linear-gradient(90deg, #667eea, #764ba2);\n";
    echo "    z-index: 9999;\n";
    echo "    transform: translateX(-100%);\n";
    echo "    animation: loading-progress 2s ease-in-out infinite;\n";
    echo "}\n";
    echo "\n";
    echo "@keyframes loading-progress {\n";
    echo "    0% { transform: translateX(-100%); }\n";
    echo "    50% { transform: translateX(0%); }\n";
    echo "    100% { transform: translateX(100%); }\n";
    echo "}\n";
    echo "</style>\n";
    
    echo "<script>\n";
    echo "// Show loading indicator on page navigation\n";
    echo "document.addEventListener('DOMContentLoaded', function() {\n";
    echo "    const loadingIndicator = document.createElement('div');\n";
    echo "    loadingIndicator.className = 'page-loading';\n";
    echo "    loadingIndicator.style.display = 'none';\n";
    echo "    document.body.appendChild(loadingIndicator);\n";
    echo "    \n";
    echo "    // Show on form submission\n";
    echo "    document.addEventListener('submit', function(e) {\n";
    echo "        if (e.target.matches('form:not([data-no-loading])')) {\n";
    echo "            loadingIndicator.style.display = 'block';\n";
    echo "        }\n";
    echo "    });\n";
    echo "    \n";
    echo "    // Show on AJAX navigation\n";
    echo "    document.addEventListener('click', function(e) {\n";
    echo "        if (e.target.matches('a[data-ajax]')) {\n";
    echo "            loadingIndicator.style.display = 'block';\n";
    echo "        }\n";
    echo "    });\n";
    echo "    \n";
    echo "    // Hide when page is fully loaded\n";
    echo "    window.addEventListener('load', function() {\n";
    echo "        setTimeout(() => {\n";
    echo "            loadingIndicator.style.display = 'none';\n";
    echo "        }, 500);\n";
    echo "    });\n";
    echo "});\n";
    echo "</script>\n";
}

/**
 * Initialize modern image lazy loading
 */
function initModernLazyLoading() {
    echo "<script>\n";
    echo "document.addEventListener('DOMContentLoaded', function() {\n";
    echo "    if ('IntersectionObserver' in window) {\n";
    echo "        const lazyImages = document.querySelectorAll('img[data-src]');\n";
    echo "        \n";
    echo "        const imageObserver = new IntersectionObserver((entries, observer) => {\n";
    echo "            entries.forEach(entry => {\n";
    echo "                if (entry.isIntersecting) {\n";
    echo "                    const img = entry.target;\n";
    echo "                    img.src = img.dataset.src;\n";
    echo "                    img.classList.remove('loading');\n";
    echo "                    img.classList.add('loaded');\n";
    echo "                    img.removeAttribute('data-src');\n";
    echo "                    observer.unobserve(img);\n";
    echo "                }\n";
    echo "            });\n";
    echo "        }, {\n";
    echo "            rootMargin: '50px 0px',\n";
    echo "            threshold: 0.01\n";
    echo "        });\n";
    echo "        \n";
    echo "        lazyImages.forEach(img => {\n";
    echo "            img.classList.add('loading');\n";
    echo "            imageObserver.observe(img);\n";
    echo "        });\n";
    echo "    } else {\n";
    echo "        // Fallback for older browsers\n";
    echo "        const lazyImages = document.querySelectorAll('img[data-src]');\n";
    echo "        lazyImages.forEach(img => {\n";
    echo "            img.src = img.dataset.src;\n";
    echo "            img.removeAttribute('data-src');\n";
    echo "        });\n";
    echo "    }\n";
    echo "});\n";
    echo "</script>\n";
}

/**
 * Add modern service worker for caching
 */
function addModernServiceWorker() {
    echo "<script>\n";
    echo "// Register service worker for modern caching\n";
    echo "if ('serviceWorker' in navigator && location.protocol === 'https:') {\n";
    echo "    window.addEventListener('load', function() {\n";
    echo "        navigator.serviceWorker.register('/sw.js')\n";
    echo "            .then(function(registration) {\n";
    echo "                console.log('SW registered: ', registration);\n";
    echo "            })\n";
    echo "            .catch(function(registrationError) {\n";
    echo "                console.log('SW registration failed: ', registrationError);\n";
    echo "            });\n";
    echo "    });\n";
    echo "}\n";
    echo "</script>\n";
}

/**
 * Complete modern assets integration
 */
function loadAllModernAssets() {
    addModernMetaTags();
    loadModernCSS();
    loadModernJS();
    initModernFormValidation();
    addModernLoadingIndicators();
    initModernLazyLoading();
    
    // Only add service worker in production
    if (!isLocalhost()) {
        addModernServiceWorker();
    }
}

/**
 * Check if running on localhost
 */
function isLocalhost() {
    return in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

/**
 * Get CSRF token (placeholder - implement based on your auth system)
 */
function getCsrfToken() {
    // This should be implemented based on your authentication system
    // For now, return a simple token
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
