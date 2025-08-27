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

/**
 * Global toggleFavorite function for backward compatibility
 * Used by onclick handlers in HTML templates
 */
function toggleFavorite(postId, buttonElement) {
  // Check if user is logged in using global config
  if (!window.Tro365Config || !window.Tro365Config.isLoggedIn) {
    // Show toast notification if available
    if (window.TroToast && typeof window.TroToast.info === "function") {
      window.TroToast.info("Vui lòng đăng nhập để sử dụng tính năng này");
    } else {
      alert("Vui lòng đăng nhập để sử dụng tính năng này");
    }
    setTimeout(() => {
      window.location.href = "/login";
    }, 1500);
    return;
  }

  // Get button element if not provided
  if (!buttonElement) {
    buttonElement = event?.target?.closest("button");
  }

  if (!buttonElement) {
    console.error("Cannot find button element for favorite toggle");
    return;
  }

  // Prevent multiple clicks
  if (buttonElement.disabled) {
    return;
  }

  // Store original state for rollback
  const heartIcon = buttonElement.querySelector("i");
  const textSpan = buttonElement.querySelector("span");
  const originalHeartClasses = heartIcon ? heartIcon.className : "";
  const originalText = textSpan ? textSpan.textContent : "";
  const originalButtonClasses = buttonElement.className;

  // Set loading state
  buttonElement.disabled = true;
  if (heartIcon) {
    heartIcon.className = "fas fa-spinner fa-spin";
  }
  if (textSpan) {
    textSpan.textContent = "Đang xử lý...";
  }
  buttonElement.classList.add("loading");

  // AJAX call to toggle favorite
  fetch("/api/favorites/toggle", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: JSON.stringify({ postId: parseInt(postId) }),
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update button state based on response
        if (data.data && data.data.favorited) {
          // Added to favorites
          buttonElement.classList.add("favorited");
          if (heartIcon) {
            heartIcon.className = "fas fa-heart text-danger";
          }
          if (textSpan) {
            textSpan.textContent = "Đã yêu thích";
          }
          buttonElement.title = "Xóa khỏi yêu thích";
        } else {
          // Removed from favorites
          buttonElement.classList.remove("favorited");
          if (heartIcon) {
            heartIcon.className = "far fa-heart";
          }
          if (textSpan) {
            textSpan.textContent = "Yêu thích";
          }
          buttonElement.title = "Thêm vào yêu thích";
        }

        // Show success message
        if (window.TroToast && typeof window.TroToast.success === "function") {
          window.TroToast.success(data.message);
        }

        // Special handling for favorites page - remove item from DOM if unfavorited
        if (
          window.location.hash === "#favorites" &&
          data.data &&
          data.data.favorited === false
        ) {
          const favoriteCard = buttonElement.closest(
            ".favorite-card, .glass-container, .col-lg-6, .col-xl-4, .col-lg-4, .col-md-6"
          );

          if (favoriteCard) {
            // Fade out animation
            favoriteCard.style.transition =
              "opacity 0.3s ease, transform 0.3s ease";
            favoriteCard.style.opacity = "0";
            favoriteCard.style.transform = "scale(0.95)";

            setTimeout(() => {
              favoriteCard.remove();

              // Update favorites count in heading
              const favoritesHeading = Array.from(
                document.querySelectorAll("h3")
              ).find(h3 => h3.textContent.includes("Bài đăng yêu thích"));
              if (favoritesHeading) {
                const currentCount = parseInt(
                  favoritesHeading.textContent.match(/\((\d+)\)/)?.[1] || "0"
                );
                const newCount = Math.max(0, currentCount - 1);
                favoritesHeading.innerHTML = favoritesHeading.innerHTML.replace(
                  /\(\d+\)/,
                  `(${newCount})`
                );

                // Show empty state if no favorites left
                if (newCount === 0) {
                  const favoritesContainer = document.querySelector(
                    ".row.g-4.mb-4, .row"
                  );
                  if (favoritesContainer) {
                    favoritesContainer.innerHTML = `
                      <div class="col-12">
                        <div class="glass-container text-center py-5">
                          <div class="glass-icon mx-auto mb-3">
                            <i class="fas fa-heart"></i>
                          </div>
                          <h5>Chưa có bài đăng yêu thích</h5>
                          <p class="text-muted mb-4">Hãy tìm kiếm và lưu những bài đăng bạn quan tâm</p>
                          <a href="/search" class="btn-glass-primary">
                            <i class="fas fa-search me-2"></i>
                            Tìm kiếm ngay
                          </a>
                        </div>
                      </div>
                    `;
                  }
                }
              }
            }, 300);
          }
        }
      } else {
        // Rollback on error
        if (heartIcon) heartIcon.className = originalHeartClasses;
        if (textSpan) textSpan.textContent = originalText;
        buttonElement.className = originalButtonClasses;

        // Show error message
        if (window.TroToast && typeof window.TroToast.error === "function") {
          window.TroToast.error(data.message || "Có lỗi xảy ra");
        } else {
          alert(data.message || "Có lỗi xảy ra");
        }
      }
    })
    .catch(error => {
      console.error("Error toggling favorite:", error);

      // Rollback on error
      if (heartIcon) heartIcon.className = originalHeartClasses;
      if (textSpan) textSpan.textContent = originalText;
      buttonElement.className = originalButtonClasses;

      // Show error message
      if (window.TroToast && typeof window.TroToast.error === "function") {
        window.TroToast.error("Có lỗi xảy ra khi xử lý yêu cầu");
      } else {
        alert("Có lỗi xảy ra khi xử lý yêu cầu");
      }
    })
    .finally(() => {
      // Re-enable button
      buttonElement.disabled = false;
      buttonElement.classList.remove("loading");
    });
}

function redirectToLogin(returnUrl) {
  return window.Tro365Common.redirectToLogin(returnUrl);
}

/**
 * Global showToast function for backward compatibility
 * Used by inline JavaScript in HTML templates
 */
function showToast(message, type = "info") {
  if (window.TroToast && typeof window.TroToast[type] === "function") {
    window.TroToast[type](message);
  } else if (window.TroToast && typeof window.TroToast.show === "function") {
    window.TroToast.show(message, type);
  } else {
    // Fallback to alert
    alert(message);
  }
}

// toggleFavorite wrapper removed to avoid conflict with page-specific implementations
