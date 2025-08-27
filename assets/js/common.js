/**
 * Common JavaScript Functions
 * Tro365 - Website thuê trọ
 *
 * Shared functions used across multiple pages
 */

window.Tro365Common = {
  // Cache for API responses to avoid duplicate requests
  _cache: {
    provinces: null,
    districts: {},
    wards: {},
  },
  /**
   * Password confirmation validation
   */
  initPasswordConfirmation: function (
    passwordId = "password",
    confirmId = "confirm_password"
  ) {
    const passwordField = document.getElementById(passwordId);
    const confirmField = document.getElementById(confirmId);

    if (!passwordField || !confirmField) return;

    confirmField.addEventListener("input", function () {
      const password = passwordField.value;
      const confirmPassword = this.value;

      if (password !== confirmPassword) {
        this.setCustomValidity("Mật khẩu xác nhận không khớp");
      } else {
        this.setCustomValidity("");
      }
    });
  },

  /**
   * Password strength checker
   */
  initPasswordStrength: function (
    passwordId = "password",
    strengthBarId = "passwordStrength"
  ) {
    const passwordField = document.getElementById(passwordId);
    const strengthBar = document.getElementById(strengthBarId);

    if (!passwordField || !strengthBar) return;

    passwordField.addEventListener("input", function () {
      const password = this.value;

      let strength = 0;
      if (password.length >= 6) strength++;
      if (password.match(/[a-z]/)) strength++;
      if (password.match(/[A-Z]/)) strength++;
      if (password.match(/[0-9]/)) strength++;
      if (password.match(/[^a-zA-Z0-9]/)) strength++;

      strengthBar.className = "password-strength";
      if (strength <= 2) {
        strengthBar.classList.add("strength-weak");
      } else if (strength <= 3) {
        strengthBar.classList.add("strength-medium");
      } else {
        strengthBar.classList.add("strength-strong");
      }
    });
  },

  /**
   * Auto-dismiss dismissible alerts only (notification alerts, not content alerts)
   */
  initAutoDismissAlerts: function (timeout = 5000) {
    setTimeout(function () {
      // Only dismiss alerts that are explicitly marked as dismissible (notification alerts)
      const alerts = document.querySelectorAll(".alert.alert-dismissible");
      alerts.forEach(function (alert) {
        if (bootstrap.Alert) {
          const bsAlert = new bootstrap.Alert(alert);
          bsAlert.close();
        }
      });
    }, timeout);
  },

  /**
   * Load provinces into select element with caching
   */
  loadProvinces: function (selectId = "province", selectedValue = "") {
    const provinceSelect = document.getElementById(selectId);
    if (!provinceSelect) return;

    // Check cache first
    if (this._cache.provinces) {
      this._populateProvinceSelect(
        provinceSelect,
        this._cache.provinces,
        selectedValue
      );
      return Promise.resolve(this._cache.provinces);
    }

    // Fetch from API if not cached
    return fetch("/api/locations/provinces")
      .then(response => response.json())
      .then(data => {
        // Cache the data
        this._cache.provinces = data;
        this._populateProvinceSelect(provinceSelect, data, selectedValue);
        return data;
      })
      .catch(error => {
        console.error("Error loading provinces:", error);
        throw error;
      });
  },

  /**
   * Helper function to populate province select
   */
  _populateProvinceSelect: function (provinceSelect, data, selectedValue = "") {
    // Clear existing options except first one
    const firstOption = provinceSelect.querySelector("option");
    provinceSelect.innerHTML = "";
    if (firstOption) {
      provinceSelect.appendChild(firstOption);
    }

    data.forEach(province => {
      const option = document.createElement("option");
      option.value = province.ID;
      option.textContent = province.TenTT;
      if (province.ID == selectedValue) {
        option.selected = true;
      }
      provinceSelect.appendChild(option);
    });
  },

  /**
   * Get cached provinces data
   */
  getCachedProvinces: function () {
    return this._cache.provinces;
  },

  /**
   * Location cascading dropdowns
   */
  initLocationDropdowns: function (
    provinceId = "province",
    districtId = "district",
    wardId = "ward"
  ) {
    const provinceSelect = document.getElementById(provinceId);
    const districtSelect = document.getElementById(districtId);
    const wardSelect = document.getElementById(wardId);

    if (!provinceSelect || !districtSelect || !wardSelect) return;

    // Province change handler
    provinceSelect.addEventListener("change", function () {
      const selectedProvinceId = this.value;

      // Clear districts and wards
      districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
      wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';

      if (selectedProvinceId) {
        fetch(`/api/locations/districts?province_id=${selectedProvinceId}`)
          .then(response => response.json())
          .then(data => {
            data.forEach(district => {
              const option = document.createElement("option");
              option.value = district.ID;
              option.textContent = district.TenQH;
              districtSelect.appendChild(option);
            });
          })
          .catch(error => console.error("Error loading districts:", error));
      }
    });

    // District change handler
    districtSelect.addEventListener("change", function () {
      const selectedDistrictId = this.value;

      // Clear wards
      wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';

      if (selectedDistrictId) {
        fetch(`/api/locations/wards?district_id=${selectedDistrictId}`)
          .then(response => response.json())
          .then(data => {
            data.forEach(ward => {
              const option = document.createElement("option");
              option.value = ward.ID;
              option.textContent = ward.TenXP;
              wardSelect.appendChild(option);
            });
          })
          .catch(error => console.error("Error loading wards:", error));
      }
    });
  },

  /**
   * Generic modal handler
   */
  showModal: function (modalId, data = {}) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Populate modal fields with data
    Object.keys(data).forEach(key => {
      const field = modal.querySelector(
        `[name="${key}"], #modal${key.charAt(0).toUpperCase() + key.slice(1)}`
      );
      if (field) {
        field.value = data[key];
      }
    });

    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
  },

  /**
   * Generic confirmation dialog
   */
  confirmAction: function (message, callback) {
    if (confirm(message)) {
      callback();
    }
  },

  /**
   * Generic form action handler
   */
  performAction: function (action, id, formId = "actionForm") {
    const form = document.getElementById(formId);
    if (!form) return;

    const actionField = form.querySelector('[name="action"]');
    const idField = form.querySelector(
      '[name="id"], [name="post_id"], [name="user_id"], [name="contact_id"]'
    );

    if (actionField) actionField.value = action;
    if (idField) idField.value = id;

    form.submit();
  },

  /**
   * AJAX helper with CSRF token - Uses modern HTTP client if available
   */
  ajaxRequest: function (url, options = {}) {
    // Use modern HTTP client if available
    if (window.http) {
      const method = options.method || "POST";
      const data = options.body ? JSON.parse(options.body) : null;

      switch (method.toLowerCase()) {
        case "get":
          return window.http.get(url, options);
        case "post":
          return window.http.post(url, data, options);
        case "put":
          return window.http.put(url, data, options);
        case "delete":
          return window.http.delete(url, options);
        default:
          return window.http.request(method, url, data, options);
      }
    }

    // Fallback to fetch if modern HTTP client not available
    const csrfToken =
      document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") ||
      (window.Tro365Config && window.Tro365Config.csrfToken) ||
      "";

    const defaultOptions = {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": csrfToken,
      },
    };

    return fetch(url, { ...defaultOptions, ...options });
  },

  /**
   * Redirect to login page with return URL
   */
  redirectToLogin: function (returnUrl = null) {
    const currentUrl =
      returnUrl || window.location.pathname + window.location.search;
    const encodedReturnUrl = encodeURIComponent(currentUrl);
    window.location.href = `/login?return_url=${encodedReturnUrl}`;
  },

  /**
   * Toggle favorite functionality
   */
  toggleFavorite: function (postId, callback = null) {
    this.ajaxRequest("/api/toggle-favorite", {
      body: JSON.stringify({ postId: postId }),
    })
      .then(res => {
        // If using modern http client, res is already parsed JSON
        if (
          res &&
          typeof res === "object" &&
          (res.success !== undefined || res.data !== undefined)
        ) {
          return res;
        }
        // Fallback: assume fetch Response
        if (res && typeof res.json === "function") {
          return res.json();
        }
        // Unknown type
        return Promise.reject(new Error("Unexpected response type"));
      })
      .then(data => {
        if (data.success) {
          // For post-detail page compatibility
          const heartIcon = document.querySelector(".fa-heart");
          const favoriteText = document.getElementById("favoriteText");

          if (heartIcon && favoriteText) {
            if (data.data && data.data.favorited) {
              heartIcon.classList.remove("text-muted");
              favoriteText.textContent = "Đã yêu thích";
            } else {
              heartIcon.classList.add("text-muted");
              favoriteText.textContent = "Yêu thích";
            }
          }

          if (callback) callback(data);
        } else {
          console.error("Toggle favorite failed:", data.message);
          if (callback) callback(data);
        }
      })
      .catch(error => {
        console.error("Error toggling favorite:", error);
        if (callback) callback({ success: false, message: "Network error" });
      });
  },

  /**
   * Initialize all common functionality
   */
  init: function () {
    // Auto-initialize common features
    this.initPasswordConfirmation();
    this.initPasswordStrength();
    this.initAutoDismissAlerts();
    this.initLocationDropdowns();

    if (window.TRO365_DEBUG) {
      console.log("Tro365 Common JavaScript initialized");
    }
  },
};

// Auto-initialize on DOM ready
document.addEventListener("DOMContentLoaded", function () {
  window.Tro365Common.init();
});

// Global helper functions for backward compatibility
function showModal(modalId, data) {
  return window.Tro365Common.showModal(modalId, data);
}

function confirmAction(message, callback) {
  return window.Tro365Common.confirmAction(message, callback);
}

function performAction(action, id, formId) {
  return window.Tro365Common.performAction(action, id, formId);
}

function redirectToLogin(returnUrl) {
  return window.Tro365Common.redirectToLogin(returnUrl);
}

// toggleFavorite wrapper removed to avoid conflict with page-specific implementations
