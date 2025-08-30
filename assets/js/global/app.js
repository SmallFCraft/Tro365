/**
 * Modern App.js - Thay thế JavaScript cũ rườm rà
 * Tích hợp tất cả utilities hiện đại
 */

// Import modern utilities
// (These would be loaded via script tags in production)

/**
 * Modern App Class - Central application controller
 */
class ModernApp {
  constructor() {
    this.initialized = false;
    this.components = new Map();
    this.config = {
      apiBaseUrl: window.location.origin,
      csrfToken: this.getCsrfToken(),
      debug: window.location.hostname === "localhost",
    };

    this.init();
  }

  /**
   * Initialize application
   */
  init() {
    if (this.initialized) return;

    // Set up global error handling
    this.setupErrorHandling();

    // Initialize components when DOM is ready
    DOM.ready(() => {
      this.initializeComponents();
      this.setupGlobalEvents();
      this.initialized = true;

      if (this.config.debug) {
        console.log("🚀 Modern App initialized");
      }
    });
  }

  /**
   * Get CSRF token from meta tag
   */
  getCsrfToken() {
    const token = DOM.$('meta[name="csrf-token"]');
    return token ? token.getAttribute("content") : null;
  }

  /**
   * Setup global error handling
   */
  setupErrorHandling() {
    window.addEventListener("error", event => {
      if (this.config.debug) {
        console.error("Global Error:", event.error);
      }
      this.handleError(event.error);
    });

    window.addEventListener("unhandledrejection", event => {
      if (this.config.debug) {
        console.error("Unhandled Promise Rejection:", event.reason);
      }
      this.handleError(event.reason);
    });
  }

  /**
   * Handle application errors
   */
  handleError(error) {
    // Log error to server in production
    if (!this.config.debug) {
      this.logErrorToServer(error);
    }

    // Show user-friendly error message
    this.showErrorNotification("Đã xảy ra lỗi. Vui lòng thử lại sau.");
  }

  /**
   * Log error to server
   */
  async logErrorToServer(error) {
    try {
      await http.post("/api/log-error", {
        message: error.message,
        stack: error.stack,
        url: window.location.href,
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString(),
      });
    } catch (e) {
      // Fail silently
    }
  }

  /**
   * Show error notification - UNIFIED with TroToast
   */
  showErrorNotification(message) {
    if (window.TroToast && typeof window.TroToast.error === "function") {
      window.TroToast.error(message);
    } else {
      alert(message);
    }
  }

  /**
   * Initialize components
   */
  initializeComponents() {
    // Initialize forms with validation
    this.initializeForms();

    // Initialize lazy loading
    this.initializeLazyLoading();

    // Initialize tooltips and popovers
    this.initializeTooltips();

    // Initialize search functionality
    this.initializeSearch();

    // Infinite scroll removed (unused - no data-infinite-scroll elements)
    // Image galleries handled by dedicated ImageGallery class
  }

  /**
   * Initialize forms
   */
  initializeForms() {
    const forms = DOM.$$("form[data-validate], form.needs-validation");

    forms.forEach(form => {
      const validator = new FormValidator(form, {
        realTimeValidation: true,
        showErrors: true,
        preventDefault: true, // Prevent FormValidator from submitting form directly
      });

      // Add custom validation rules
      this.addCustomValidationRules(validator, form);

      // Handle form submission
      form.addEventListener("form:valid", async event => {
        await this.handleFormSubmission(form, event.detail.formData);
      });
    });
  }

  /**
   * Add custom validation rules
   */
  addCustomValidationRules(validator, form) {
    // Phone number validation
    const phoneFields = form.querySelectorAll(
      'input[type="tel"], input[name*="phone"]'
    );
    phoneFields.forEach(field => {
      validator.addRule(
        field.name,
        FormValidator.rules.phone,
        "Số điện thoại không hợp lệ"
      );
    });

    // Password confirmation
    const passwordField = form.querySelector('input[name="password"]');
    const confirmField = form.querySelector(
      'input[name="password_confirmation"]'
    );
    if (passwordField && confirmField) {
      validator.addRule(
        "password_confirmation",
        FormValidator.rules.match("password"),
        "Xác nhận mật khẩu không khớp"
      );
    }
  }

  /**
   * Handle form submission
   */
  async handleFormSubmission(form, formData) {
    const action = form.getAttribute("action") || window.location.pathname;
    const method = form.getAttribute("method") || "POST";

    try {
      const response = await http.request(method, action, formData);

      if (response.success) {
        this.handleFormSuccess(form, response);
      } else {
        this.handleFormError(form, response);
      }
    } catch (error) {
      this.handleFormError(form, { message: error.message });
    }
  }

  /**
   * Handle form success
   */
  handleFormSuccess(form, response) {
    if (response.redirect) {
      window.location.href = response.redirect;
    } else if (response.message) {
      this.showSuccessNotification(response.message);
      form.reset();
    }
  }

  /**
   * Handle form error
   */
  handleFormError(form, response) {
    if (response.errors) {
      // Show field-specific errors
      Object.entries(response.errors).forEach(([field, message]) => {
        const fieldElement = form.querySelector(`[name="${field}"]`);
        if (fieldElement) {
          this.showFieldError(fieldElement, message);
        }
      });
    } else if (response.message) {
      this.showErrorNotification(response.message);
    }
  }

  /**
   * Show field error
   */
  showFieldError(field, message) {
    field.classList.add("is-invalid");

    let errorContainer = field.parentNode.querySelector(".invalid-feedback");
    if (!errorContainer) {
      errorContainer = DOM.createElement("div", {
        className: "invalid-feedback",
      });
      field.parentNode.appendChild(errorContainer);
    }

    errorContainer.textContent = message;
    errorContainer.style.display = "block";
  }

  /**
   * Initialize lazy loading
   */
  initializeLazyLoading() {
    if ("IntersectionObserver" in window) {
      const lazyImages = DOM.$$('img[data-src], img[loading="lazy"]');

      const imageObserver = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            if (img.dataset.src) {
              img.src = img.dataset.src;
              img.removeAttribute("data-src");
            }
            img.classList.add("loaded");
            imageObserver.unobserve(img);
          }
        });
      });

      lazyImages.forEach(img => imageObserver.observe(img));
    }
  }

  /**
   * Initialize tooltips
   */
  initializeTooltips() {
    const tooltipElements = DOM.$$("[data-tooltip], [title]");

    tooltipElements.forEach(element => {
      const title = element.dataset.tooltip || element.title;
      if (title) {
        element.addEventListener("mouseenter", () => {
          this.showTooltip(element, title);
        });

        element.addEventListener("mouseleave", () => {
          this.hideTooltip();
        });
      }
    });
  }

  /**
   * Show tooltip
   */
  showTooltip(element, text) {
    const tooltip = DOM.createElement("div", {
      className: "tooltip-modern",
      innerHTML: text,
    });

    document.body.appendChild(tooltip);

    const rect = element.getBoundingClientRect();
    tooltip.style.left =
      rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px";
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + "px";

    DOM.fadeIn(tooltip, 200);

    this.currentTooltip = tooltip;
  }

  /**
   * Hide tooltip
   */
  hideTooltip() {
    if (this.currentTooltip) {
      DOM.fadeOut(this.currentTooltip, 200);
      setTimeout(() => {
        if (this.currentTooltip && this.currentTooltip.parentNode) {
          this.currentTooltip.parentNode.removeChild(this.currentTooltip);
        }
        this.currentTooltip = null;
      }, 200);
    }
  }

  /**
   * Initialize search functionality
   */
  initializeSearch() {
    const searchInputs = DOM.$$("input[data-search], .search-input");

    searchInputs.forEach(input => {
      const searchHandler = DOM.debounce(async event => {
        const query = event.target.value.trim();
        if (query.length >= 2) {
          await this.performSearch(query, input);
        }
      }, 300);

      input.addEventListener("input", searchHandler);
    });
  }

  /**
   * Perform search
   */
  async performSearch(query, input) {
    const searchUrl = input.dataset.searchUrl || "/api/search";
    const resultsContainer = DOM.$(
      input.dataset.resultsContainer || ".search-results"
    );

    if (!resultsContainer) return;

    try {
      resultsContainer.innerHTML =
        '<div class="loading">Đang tìm kiếm...</div>';

      const response = await http.get(searchUrl, { params: { q: query } });

      if (response.results && response.results.length > 0) {
        resultsContainer.innerHTML = this.renderSearchResults(response.results);
      } else {
        resultsContainer.innerHTML =
          '<div class="no-results">Không tìm thấy kết quả</div>';
      }
    } catch (error) {
      resultsContainer.innerHTML = '<div class="error">Lỗi tìm kiếm</div>';
    }
  }

  /**
   * Render search results
   */
  renderSearchResults(results) {
    return results
      .map(
        result => `
            <div class="search-result-item">
                <a href="${result.url}" class="search-result-link">
                    <div class="search-result-title">${result.title}</div>
                    <div class="search-result-description">${
                      result.description || ""
                    }</div>
                </a>
            </div>
        `
      )
      .join("");
  }

  /* initializeInfiniteScroll() method removed (unused) */

  /* initializeImageGalleries() and openLightbox() methods removed (handled by dedicated ImageGallery class) */

  /**
   * Setup global events
   */
  setupGlobalEvents() {
    // AJAX navigation removed (unused - no data-ajax links in codebase)
  }

  /* handleAjaxNavigation() method removed (unused) */

  /**
   * Show success notification - UNIFIED with TroToast
   */
  showSuccessNotification(message) {
    if (window.TroToast && typeof window.TroToast.success === "function") {
      window.TroToast.success(message);
    } else {
      alert(message);
    }
  }

  /**
   * Register component
   */
  registerComponent(name, component) {
    this.components.set(name, component);
  }

  /**
   * Get component
   */
  getComponent(name) {
    return this.components.get(name);
  }
}

// Initialize app when script loads
const app = new ModernApp();

// Export for global use
window.App = app;
