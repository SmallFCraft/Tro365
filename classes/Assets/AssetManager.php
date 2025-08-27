<?php

namespace Tro365\Assets;

class AssetManager
{
    private string $baseUrl;
    private array $metaTags = [];
    private array $css = [];
    private array $js = [];
    private bool $enableFormValidation = false;
    private bool $enableLazyLoading = false;
    private bool $enableServiceWorker = false;
    private string $version = '1.0.0';

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?: (function_exists('app_url') ? rtrim(app_url(''), '/') : (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    }

    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function addMetaTags(array $meta): self
    {
        $this->metaTags = array_merge($this->metaTags, $meta);
        return $this;
    }

    public function enqueueCSS(array $paths): self
    {
        foreach ($paths as $p) {
            $this->css[] = $p;
        }
        return $this;
    }

    public function enqueueJS(array $paths): self
    {
        foreach ($paths as $p) {
            $this->js[] = $p;
        }
        return $this;
    }

    public function enableFormValidation(): self
    {
        $this->enableFormValidation = true;
        return $this;
    }

    public function enableLazyLoading(): self
    {
        $this->enableLazyLoading = true;
        return $this;
    }

    public function enableServiceWorkerIfProd(): self
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1']);
        $this->enableServiceWorker = !$isLocal;
        return $this;
    }

    public function renderHead(): string
    {
        $out = [];
        $out[] = '<!-- Modern Meta Tags -->';
        $out[] = '<meta charset="UTF-8">';
        $out[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $out[] = '<meta http-equiv="X-UA-Compatible" content="IE=edge">';

        // CSRF token
        if (isset($this->metaTags['csrf'])) {
            $out[] = '<meta name="csrf-token" content="' . htmlspecialchars($this->metaTags['csrf']) . '">';
        }

        // Other meta tags
        foreach ($this->metaTags as $name => $content) {
            if ($name !== 'csrf') {
                $out[] = '<meta name="' . htmlspecialchars($name) . '" content="' . htmlspecialchars($content) . '">';
            }
        }

        // Icons (avoid relying on /favicon.ico route)
        $out[] = '<link rel="icon" href="' . $this->url('/assets/images/logo/favicon.ico') . '" type="image/x-icon">';
        $out[] = '<link rel="apple-touch-icon" href="' . $this->url('/assets/images/logo/apple-touch-icon.png') . '">';

        // Connection hints
        $out[] = '<link rel="dns-prefetch" href="//cdn.jsdelivr.net">';
        $out[] = '<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>';

        // CSS Utilities + any enqueued CSS
        $out[] = '<!-- Modern CSS Utilities -->';
        $out[] = '<link rel="stylesheet" href="' . $this->url('/assets/css/modern/utilities.css') . $this->ver() . '">';
        foreach ($this->css as $css) {
            $out[] = '<link rel="stylesheet" href="' . $this->url($css) . $this->ver() . '">';
        }

        return implode("\n", $out) . "\n";
    }

    public function renderFooter(): string
    {
        $out = [];
        $out[] = '<!-- Modern JavaScript Libraries -->';
        // Core modern libraries
        $out[] = '<script src="' . $this->url('/assets/js/modern/http-client.js') . $this->ver() . '"></script>';
        $out[] = '<script src="' . $this->url('/assets/js/modern/dom-utils.js') . $this->ver() . '"></script>';
        $out[] = '<script src="' . $this->url('/assets/js/modern/form-validator.js') . $this->ver() . '"></script>';
        $out[] = '<script src="' . $this->url('/assets/js/modern/toast.js') . $this->ver() . '"></script>';
        $out[] = '<script src="' . $this->url('/assets/js/modern/app.js') . $this->ver() . '"></script>';
        // Local vendor libs
        $out[] = '<!-- Modern JavaScript Libraries (Local) -->';
        $out[] = '<script src="' . $this->url('/assets/js/vendor/alpine.min.js') . '" defer></script>';
        $out[] = '<script src="' . $this->url('/assets/js/vendor/axios.min.js') . '"></script>';
        $out[] = '<script src="' . $this->url('/assets/js/vendor/dayjs.min.js') . '"></script>';

        // Initialize modern features
        $out[] = '<script>' . $this->jsInit() . '</script>';

        // Optional features
        if ($this->enableFormValidation) {
            $out[] = '<script>document.addEventListener("DOMContentLoaded",function(){ if(window.ModernFormValidator){ ModernFormValidator.init(); } });</script>';
        }
        if ($this->enableLazyLoading) {
            $out[] = '<script>' . $this->lazyLoadingScript() . '</script>';
        }
        if ($this->enableServiceWorker) {
            $out[] = '<script>' . $this->serviceWorkerScript() . '</script>';
        }

        // Any enqueued JS after core
        foreach ($this->js as $js) {
            $out[] = '<script src="' . $this->url($js) . $this->ver() . '"></script>';
        }

        return implode("\n", $out) . "\n";
    }

    private function jsInit(): string
    {
        return <<<JS
// Initialize modern features
document.addEventListener('DOMContentLoaded', function() {
  const tokenMeta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : '';
  if (csrfToken && window.http && typeof window.http.setCsrfToken === 'function') {
    window.http.setCsrfToken(csrfToken);
  }
  const tooltips = document.querySelectorAll('[data-tooltip]');
  tooltips.forEach(el => { el.setAttribute('title', el.dataset.tooltip); });
  console.log('🚀 Modern assets loaded successfully');
});
JS;
    }

    private function lazyLoadingScript(): string
    {
        return <<<JS
// Modern image lazy loading
document.addEventListener('DOMContentLoaded', function() {
  if ('IntersectionObserver' in window) {
    const lazyImages = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.remove('loading');
          img.classList.add('loaded');
          img.removeAttribute('data-src');
          observer.unobserve(img);
        }
      });
    }, { rootMargin: '50px 0px', threshold: 0.01 });
    lazyImages.forEach(img => { img.classList.add('loading'); imageObserver.observe(img); });
  } else {
    const lazyImages = document.querySelectorAll('img[data-src]');
    lazyImages.forEach(img => { img.src = img.dataset.src; img.classList.add('loaded'); img.removeAttribute('data-src'); });
  }
});
JS;
    }

    private function serviceWorkerScript(): string
    {
        return <<<JS
// Register service worker for modern caching
if ('serviceWorker' in navigator && location.protocol === 'https:') {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/sw.js')
      .then(function(reg) { console.log('SW registered: ', reg); })
      .catch(function(err) { console.log('SW registration failed: ', err); });
  });
}
JS;
    }

    private function url(string $path): string
    {
        // if path is absolute (starts with http), return as is
        if (preg_match('#^https?://#', $path)) { return $path; }
        // normalize
        if ($path === '' || $path[0] !== '/') { $path = '/' . $path; }
        return rtrim($this->baseUrl, '/') . $path;
    }

    private function ver(): string
    {
        return '?v=' . rawurlencode($this->version);
    }
}
