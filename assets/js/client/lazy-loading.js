/**
 * Lazy Loading Images for Performance Optimization
 * Tro365 - Website thuê trọ
 * Saves bandwidth and improves page load speed
 */

class LazyImageLoader {
  constructor(options = {}) {
    this.options = {
      rootMargin: "50px 0px",
      threshold: 0.01,
      loadingClass: "lazy-loading",
      loadedClass: "lazy-loaded",
      errorClass: "lazy-error",
      ...options,
    };

    this.observer = null;
    this.init();
  }

  init() {
    // Check if Intersection Observer is supported
    if (!("IntersectionObserver" in window)) {
      // Fallback: load all images immediately
      this.loadAllImages();
      return;
    }

    // Create intersection observer
    this.observer = new IntersectionObserver(
      this.handleIntersection.bind(this),
      {
        rootMargin: this.options.rootMargin,
        threshold: this.options.threshold,
      }
    );

    // Start observing lazy images
    this.observeImages();
  }

  observeImages() {
    const lazyImages = document.querySelectorAll(
      'img[data-src], img[loading="lazy"]'
    );

    lazyImages.forEach(img => {
      // Add loading class
      img.classList.add(this.options.loadingClass);

      // Start observing
      this.observer.observe(img);
    });

    if (window.TRO365_DEBUG) {
      console.log(`LazyImageLoader: Observing ${lazyImages.length} images`);
    }
  }

  handleIntersection(entries) {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        this.loadImage(img);
        this.observer.unobserve(img);
      }
    });
  }

  loadImage(img) {
    const src = img.dataset.src || img.src;

    if (!src) return;

    // Create new image to preload
    const imageLoader = new Image();

    imageLoader.onload = () => {
      // Image loaded successfully
      img.src = src;
      img.classList.remove(this.options.loadingClass);
      img.classList.add(this.options.loadedClass);

      // Remove data-src attribute
      if (img.dataset.src) {
        delete img.dataset.src;
      }
    };

    imageLoader.onerror = () => {
      // Image failed to load
      img.classList.remove(this.options.loadingClass);
      img.classList.add(this.options.errorClass);

      // Try fallback image if available
      const fallback = img.dataset.fallback || img.getAttribute("onerror");
      if (fallback && fallback.includes("src=")) {
        const fallbackSrc =
          fallback.match(/src='([^']+)'/)?.[1] ||
          fallback.match(/src="([^"]+)"/)?.[1];
        if (fallbackSrc) {
          img.src = fallbackSrc;
        }
      }
    };

    // Start loading
    imageLoader.src = src;
  }

  loadAllImages() {
    // Fallback for browsers without Intersection Observer
    const lazyImages = document.querySelectorAll("img[data-src]");

    lazyImages.forEach(img => {
      if (img.dataset.src) {
        img.src = img.dataset.src;
        delete img.dataset.src;
      }
    });
  }

  // Public method to add new images to observation
  observe(img) {
    if (this.observer && img) {
      img.classList.add(this.options.loadingClass);
      this.observer.observe(img);
    }
  }

  // Public method to stop observing
  disconnect() {
    if (this.observer) {
      this.observer.disconnect();
    }
  }
}

// CSS for lazy loading states
const lazyLoadingCSS = `
  .lazy-loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading-shimmer 1.5s infinite;
    min-height: 200px;
  }
  
  [data-theme="dark"] .lazy-loading {
    background: linear-gradient(90deg, #2a2a2a 25%, #3a3a3a 50%, #2a2a2a 75%);
    background-size: 200% 100%;
  }
  
  @keyframes loading-shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
  }
  
  .lazy-loaded {
    animation: fade-in 0.3s ease-in-out;
  }
  
  @keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  
  .lazy-error {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    color: #6c757d;
  }
  
  .lazy-error::before {
    content: "⚠️ Image not available";
    font-size: 14px;
  }
`;

// Inject CSS
const style = document.createElement("style");
style.textContent = lazyLoadingCSS;
document.head.appendChild(style);

// Auto-initialize on DOM ready
document.addEventListener("DOMContentLoaded", () => {
  window.lazyImageLoader = new LazyImageLoader();
});

// Export for manual use
window.LazyImageLoader = LazyImageLoader;
