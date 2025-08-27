/**
 * Modern DOM Utilities - Thay thế jQuery và vanilla DOM manipulation rườm rà
 * Lightweight utility functions cho DOM operations
 */
class DOMUtils {
  /**
   * Query selector with context
   */
  static $(selector, context = document) {
    return context.querySelector(selector);
  }

  /**
   * Query selector all with context
   */
  static $$(selector, context = document) {
    return Array.from(context.querySelectorAll(selector));
  }

  /**
   * Create element with attributes and content
   */
  static createElement(tag, attributes = {}, content = "") {
    const element = document.createElement(tag);

    Object.entries(attributes).forEach(([key, value]) => {
      if (key === "className") {
        element.className = value;
      } else if (key === "dataset") {
        Object.entries(value).forEach(([dataKey, dataValue]) => {
          element.dataset[dataKey] = dataValue;
        });
      } else {
        element.setAttribute(key, value);
      }
    });

    if (content) {
      if (typeof content === "string") {
        element.innerHTML = content;
      } else {
        element.appendChild(content);
      }
    }

    return element;
  }

  /**
   * Add event listener with delegation
   */
  static on(element, event, selector, handler) {
    if (typeof selector === "function") {
      handler = selector;
      selector = null;
    }

    element.addEventListener(event, e => {
      if (selector) {
        if (e.target.matches(selector)) {
          handler.call(e.target, e);
        }
      } else {
        handler.call(element, e);
      }
    });
  }

  /**
   * Remove element
   */
  static remove(element) {
    if (element && element.parentNode) {
      element.parentNode.removeChild(element);
    }
  }

  /**
   * Toggle class
   */
  static toggleClass(element, className, force) {
    return element.classList.toggle(className, force);
  }

  /**
   * Add class
   */
  static addClass(element, className) {
    element.classList.add(className);
  }

  /**
   * Remove class
   */
  static removeClass(element, className) {
    element.classList.remove(className);
  }

  /**
   * Has class
   */
  static hasClass(element, className) {
    return element.classList.contains(className);
  }

  /**
   * Get/Set attribute
   */
  static attr(element, name, value) {
    if (value === undefined) {
      return element.getAttribute(name);
    }
    element.setAttribute(name, value);
    return element;
  }

  /**
   * Get/Set data attribute
   */
  static data(element, name, value) {
    if (value === undefined) {
      return element.dataset[name];
    }
    element.dataset[name] = value;
    return element;
  }

  /**
   * Show element
   */
  static show(element, display = "block") {
    element.style.display = display;
  }

  /**
   * Hide element
   */
  static hide(element) {
    element.style.display = "none";
  }

  /**
   * Toggle visibility
   */
  static toggle(element, display = "block") {
    if (element.style.display === "none") {
      this.show(element, display);
    } else {
      this.hide(element);
    }
  }

  /**
   * Fade in element
   */
  static fadeIn(element, duration = 300) {
    element.style.opacity = "0";
    element.style.display = "block";

    const start = performance.now();

    const animate = currentTime => {
      const elapsed = currentTime - start;
      const progress = Math.min(elapsed / duration, 1);

      element.style.opacity = progress;

      if (progress < 1) {
        requestAnimationFrame(animate);
      }
    };

    requestAnimationFrame(animate);
  }

  /**
   * Fade out element
   */
  static fadeOut(element, duration = 300) {
    const start = performance.now();
    const initialOpacity = parseFloat(getComputedStyle(element).opacity);

    const animate = currentTime => {
      const elapsed = currentTime - start;
      const progress = Math.min(elapsed / duration, 1);

      element.style.opacity = initialOpacity * (1 - progress);

      if (progress >= 1) {
        element.style.display = "none";
      } else {
        requestAnimationFrame(animate);
      }
    };

    requestAnimationFrame(animate);
  }

  /**
   * Slide down element
   */
  static slideDown(element, duration = 300) {
    element.style.display = "block";
    const height = element.scrollHeight;
    element.style.height = "0px";
    element.style.overflow = "hidden";

    const start = performance.now();

    const animate = currentTime => {
      const elapsed = currentTime - start;
      const progress = Math.min(elapsed / duration, 1);

      element.style.height = height * progress + "px";

      if (progress >= 1) {
        element.style.height = "";
        element.style.overflow = "";
      } else {
        requestAnimationFrame(animate);
      }
    };

    requestAnimationFrame(animate);
  }

  /**
   * Slide up element
   */
  static slideUp(element, duration = 300) {
    const height = element.scrollHeight;
    element.style.height = height + "px";
    element.style.overflow = "hidden";

    const start = performance.now();

    const animate = currentTime => {
      const elapsed = currentTime - start;
      const progress = Math.min(elapsed / duration, 1);

      element.style.height = height * (1 - progress) + "px";

      if (progress >= 1) {
        element.style.display = "none";
        element.style.height = "";
        element.style.overflow = "";
      } else {
        requestAnimationFrame(animate);
      }
    };

    requestAnimationFrame(animate);
  }

  /**
   * Get element position
   */
  static position(element) {
    const rect = element.getBoundingClientRect();
    return {
      top: rect.top + window.scrollY,
      left: rect.left + window.scrollX,
      width: rect.width,
      height: rect.height,
    };
  }

  /**
   * Scroll to element
   */
  static scrollTo(element, options = {}) {
    const defaultOptions = {
      behavior: "smooth",
      block: "start",
      inline: "nearest",
    };

    element.scrollIntoView({ ...defaultOptions, ...options });
  }

  /**
   * Debounce function
   */
  static debounce(func, wait, immediate = false) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        timeout = null;
        if (!immediate) func.apply(this, args);
      };
      const callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func.apply(this, args);
    };
  }

  /**
   * Throttle function
   */
  static throttle(func, limit) {
    let inThrottle;
    return function executedFunction(...args) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => (inThrottle = false), limit);
      }
    };
  }

  /**
   * Wait for DOM ready
   */
  static ready(callback) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", callback);
    } else {
      callback();
    }
  }

  /**
   * Load script dynamically
   */
  static loadScript(src) {
    return new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = src;
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  /**
   * Load CSS dynamically
   */
  static loadCSS(href) {
    return new Promise((resolve, reject) => {
      const link = document.createElement("link");
      link.rel = "stylesheet";
      link.href = href;
      link.onload = resolve;
      link.onerror = reject;
      document.head.appendChild(link);
    });
  }
}

// Export for global use
window.DOM = DOMUtils;
window.$ = DOMUtils.$;
window.$$ = DOMUtils.$$;
