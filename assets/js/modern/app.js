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
   * Show error notification
   */
  showErrorNotification(message) {
    // Use SweetAlert2 if available, otherwise fallback to alert
    if (window.Swal) {
      Swal.fire({
        icon: "error",
        title: "Lỗi",
        text: message,
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 5000,
      });
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

    // Initialize infinite scroll
    this.initializeInfiniteScroll();

    // Initialize image galleries
    this.initializeImageGalleries();
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

  /**
   * Initialize infinite scroll
   */
  initializeInfiniteScroll() {
    const containers = DOM.$$("[data-infinite-scroll]");

    containers.forEach(container => {
      const loadMoreUrl = container.dataset.loadMoreUrl;
      if (!loadMoreUrl) return;

      let page = 1;
      let loading = false;

      const observer = new IntersectionObserver(async entries => {
        if (entries[0].isIntersecting && !loading) {
          loading = true;
          page++;

          try {
            const response = await http.get(loadMoreUrl, { params: { page } });

            if (response.html) {
              container.insertAdjacentHTML("beforeend", response.html);
            }

            if (!response.hasMore) {
              observer.disconnect();
            }
          } catch (error) {
            console.error("Infinite scroll error:", error);
          } finally {
            loading = false;
          }
        }
      });

      // Observe the last element in container
      const lastElement = container.lastElementChild;
      if (lastElement) {
        observer.observe(lastElement);
      }
    });
  }

  /**
   * Initialize image galleries
   */
  initializeImageGalleries() {
    const galleries = DOM.$$("[data-gallery]");

    galleries.forEach(gallery => {
      const images = gallery.querySelectorAll("img[data-full]");

      images.forEach((img, index) => {
        img.addEventListener("click", () => {
          this.openLightbox(images, index);
        });
      });
    });
  }

  /**
   * Open lightbox
   */
  openLightbox(images, startIndex) {
    // Simple lightbox implementation
    const lightbox = DOM.createElement("div", {
      className: "lightbox-overlay",
      innerHTML: `
                <div class="lightbox-container">
                    <button class="lightbox-close">&times;</button>
                    <button class="lightbox-prev">&#8249;</button>
                    <img class="lightbox-image" src="${
                      images[startIndex].dataset.full
                    }" alt="">
                    <button class="lightbox-next">&#8250;</button>
                    <div class="lightbox-counter">${startIndex + 1} / ${
        images.length
      }</div>
                </div>
            `,
    });

    document.body.appendChild(lightbox);
    document.body.style.overflow = "hidden";

    let currentIndex = startIndex;

    // Event handlers
    const closeBtn = lightbox.querySelector(".lightbox-close");
    const prevBtn = lightbox.querySelector(".lightbox-prev");
    const nextBtn = lightbox.querySelector(".lightbox-next");
    const img = lightbox.querySelector(".lightbox-image");
    const counter = lightbox.querySelector(".lightbox-counter");

    const updateImage = index => {
      img.src = images[index].dataset.full;
      counter.textContent = `${index + 1} / ${images.length}`;
    };

    closeBtn.addEventListener("click", () => {
      document.body.removeChild(lightbox);
      document.body.style.overflow = "";
    });

    prevBtn.addEventListener("click", () => {
      currentIndex = currentIndex > 0 ? currentIndex - 1 : images.length - 1;
      updateImage(currentIndex);
    });

    nextBtn.addEventListener("click", () => {
      currentIndex = currentIndex < images.length - 1 ? currentIndex + 1 : 0;
      updateImage(currentIndex);
    });

    // Keyboard navigation
    document.addEventListener("keydown", function keyHandler(e) {
      if (e.key === "Escape") {
        closeBtn.click();
        document.removeEventListener("keydown", keyHandler);
      } else if (e.key === "ArrowLeft") {
        prevBtn.click();
      } else if (e.key === "ArrowRight") {
        nextBtn.click();
      }
    });

    DOM.fadeIn(lightbox, 300);
  }

  /**
   * Setup global events
   */
  setupGlobalEvents() {
    // Handle AJAX navigation
    document.addEventListener("click", event => {
      const link = event.target.closest("a[data-ajax]");
      if (link) {
        event.preventDefault();
        this.handleAjaxNavigation(link.href);
      }
    });

    // Handle back button
    window.addEventListener("popstate", event => {
      if (event.state && event.state.ajax) {
        this.handleAjaxNavigation(window.location.href, false);
      }
    });
  }

  /**
   * Handle AJAX navigation
   */
  async handleAjaxNavigation(url, pushState = true) {
    try {
      const response = await http.get(url, {
        headers: { "X-PJAX": "true" },
      });

      if (response.html) {
        const contentContainer =
          DOM.$("#main-content") || DOM.$("main") || document.body;
        contentContainer.innerHTML = response.html;

        if (pushState) {
          history.pushState({ ajax: true }, "", url);
        }

        // Reinitialize components for new content
        this.initializeComponents();
      }
    } catch (error) {
      // Fallback to regular navigation
      window.location.href = url;
    }
  }

  /**
   * Show success notification
   */
  showSuccessNotification(message) {
    if (window.Swal) {
      Swal.fire({
        icon: "success",
        title: "Thành công",
        text: message,
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
      });
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
