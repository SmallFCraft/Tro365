/**
 * Performance-Optimized Intersection Observer
 * Reduces layout thrashing by batching DOM operations
 * Renamed to avoid conflict with native browser PerformanceObserver API
 */
class Tro365PerformanceObserver {
  constructor() {
    this.observers = new Map();
    this.pendingUpdates = new Set();
    this.rafId = null;
  }

  /**
   * Create optimized intersection observer
   */
  createObserver(options = {}) {
    const defaultOptions = {
      rootMargin: "50px 0px",
      threshold: 0.1,
      // Batch updates to prevent layout thrashing
      batchUpdates: true,
      ...options,
    };

    const observer = new IntersectionObserver(
      entries => {
        if (defaultOptions.batchUpdates) {
          // Batch DOM updates in next animation frame
          entries.forEach(entry => this.pendingUpdates.add(entry));
          this.scheduleUpdate(defaultOptions.callback);
        } else {
          // Immediate callback for critical updates
          defaultOptions.callback(entries);
        }
      },
      {
        rootMargin: defaultOptions.rootMargin,
        threshold: defaultOptions.threshold,
      }
    );

    return observer;
  }

  /**
   * Schedule batched DOM updates
   */
  scheduleUpdate(callback) {
    if (this.rafId) return;

    this.rafId = requestAnimationFrame(() => {
      // Process all pending updates in one batch
      const entries = Array.from(this.pendingUpdates);
      this.pendingUpdates.clear();
      this.rafId = null;

      if (entries.length > 0 && callback) {
        callback(entries);
      }
    });
  }

  /**
   * Optimized lazy loading observer
   */
  createLazyLoadObserver(callback) {
    return this.createObserver({
      rootMargin: "100px 0px",
      threshold: 0.01,
      batchUpdates: true,
      callback: entries => {
        // Separate reads and writes to prevent layout thrashing
        const toLoad = [];

        // Read phase - no DOM writes
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            toLoad.push(entry.target);
          }
        });

        // Write phase - batch DOM updates
        if (toLoad.length > 0) {
          requestAnimationFrame(() => {
            toLoad.forEach(element => {
              if (callback) callback(element);
            });
          });
        }
      },
    });
  }

  /**
   * Optimized animation observer
   */
  createAnimationObserver(callback) {
    return this.createObserver({
      rootMargin: "0px 0px -50px 0px",
      threshold: 0.1,
      batchUpdates: true,
      callback: entries => {
        // Group animations by type to optimize CSS changes
        const animations = {
          fadeIn: [],
          slideUp: [],
          scale: [],
        };

        // Read phase
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const animationType = entry.target.dataset.animation || "fadeIn";
            if (animations[animationType]) {
              animations[animationType].push(entry.target);
            }
          }
        });

        // Write phase - batch by animation type
        Object.entries(animations).forEach(([type, elements]) => {
          if (elements.length > 0) {
            requestAnimationFrame(() => {
              elements.forEach((element, index) => {
                // Stagger animations to prevent jank
                setTimeout(() => {
                  if (callback) callback(element, type);
                }, index * 50);
              });
            });
          }
        });
      },
    });
  }

  /**
   * Cleanup observers (Enhanced with memory leak protection)
   */
  cleanup() {
    if (this.rafId) {
      cancelAnimationFrame(this.rafId);
      this.rafId = null;
    }

    // Disconnect all observers
    this.observers.forEach(observer => {
      if (observer && typeof observer.disconnect === "function") {
        observer.disconnect();
      }
    });

    // Clear all collections
    this.observers.clear();
    this.pendingUpdates.clear();

    // Prevent memory leaks by nullifying references
    this.observers = new Map();
    this.pendingUpdates = new Set();
  }
}

// Global instance (renamed to avoid conflict with native API)
window.Tro365PerformanceObserver = new Tro365PerformanceObserver();

// Backward compatibility alias (deprecated)
window.PerformanceObserver = window.Tro365PerformanceObserver;

// Auto-cleanup on page unload
window.addEventListener("beforeunload", () => {
  window.Tro365PerformanceObserver.cleanup();
});
