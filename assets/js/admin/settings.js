/**
 * Admin Settings JavaScript
 * Tro365 - Website thuê trọ
 */

window.Tro365Settings = {
  /**
   * Initialize settings functionality
   */
  init: function () {
    this.initTooltips();
    this.initFormValidation();
    this.initSettingsForm();
    this.initStyles();
    this.bindEditVersionEvents();
    this.bindTestEmailEvents();
    this.initEventDelegation();

    console.log("Tro365 Settings JavaScript initialized");
  },

  /**
   * Initialize styles
   */
  initStyles: function () {
    // Add CSS for auto-save indicator
    const settingsStyle = document.createElement("style");
    settingsStyle.textContent = `
            .auto-save-indicator {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1050;
            }
        `;
    document.head.appendChild(settingsStyle);
  },

  /**
   * Initialize event delegation for data-action buttons
   */
  initEventDelegation: function () {
    const self = this;

    // Event delegation for all buttons with data-action
    document.addEventListener("click", function (e) {
      const button = e.target.closest("[data-action]");
      if (!button) return;

      // Prevent default behavior to avoid form submission or page reload
      e.preventDefault();
      e.stopPropagation();

      const action = button.getAttribute("data-action");

      switch (action) {
        case "clear-cache":
          self.clearCache();
          break;
        case "export-settings":
          self.exportSettings();
          break;
        case "update-version":
          self.updateVersion();
          break;
        case "export-system-info":
          self.exportSystemInfo();
          break;
        case "reset-to-default":
          self.resetToDefault();
          break;
        case "save-tinymce-settings":
          saveTinyMCESettings();
          break;
        default:
          console.warn("Unknown action:", action);
      }
    });
  },

  /**
   * Initialize tooltips
   */
  initTooltips: function () {
    if (typeof bootstrap !== "undefined") {
      const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
      );
      tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
      });
    }
  },

  /**
   * Initialize form validation - Using FormValidator (standardized)
   */
  initFormValidation: function () {
    // FormValidator handles all validation automatically
    // No need for manual Bootstrap validation setup
    // Forms with .needs-validation or [data-validate] are auto-initialized by FormValidator
    console.log("Form validation delegated to FormValidator");
  },

  /**
   * Initialize settings form
   */
  initSettingsForm: function () {
    const settingsForm = document.getElementById("settingsForm");
    if (settingsForm) {
      // Auto-save functionality
      const inputs = settingsForm.querySelectorAll("input, textarea, select");
      inputs.forEach(input => {
        input.addEventListener(
          "change",
          this.debounce(() => {
            this.autoSaveSettings();
            // Auto-save indicator will be shown by autoSaveSettings() success callback
          }, 1000)
        );
      });

      // Email settings validation
      this.initEmailValidation();

      // Tab switching
      const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
      tabButtons.forEach(button => {
        button.addEventListener("shown.bs.tab", e => {
          const targetTab = e.target.getAttribute("data-bs-target");
          localStorage.setItem("admin_settings_active_tab", targetTab);
        });
      });

      // Restore active tab
      const activeTab = localStorage.getItem("admin_settings_active_tab");
      if (activeTab) {
        const tabButton = document.querySelector(
          `[data-bs-target="${activeTab}"]`
        );
        if (tabButton) {
          const tab = new bootstrap.Tab(tabButton);
          tab.show();
        }
      }
    }
  },

  /**
   * Initialize email settings validation
   */
  initEmailValidation: function () {
    const mailDriverSelect = document.querySelector(
      'select[name="mail_driver"]'
    );
    const smtpFields = [
      'input[name="mail_host"]',
      'input[name="mail_port"]',
      'select[name="mail_encryption"]',
      'input[name="mail_username"]',
      'input[name="mail_password"]',
    ];

    if (mailDriverSelect) {
      // Toggle SMTP fields based on driver selection
      const toggleSmtpFields = () => {
        const isSmtp = mailDriverSelect.value === "smtp";
        smtpFields.forEach(selector => {
          const field = document.querySelector(selector);
          if (field) {
            field.required = isSmtp;
            field.closest(".mb-3").style.opacity = isSmtp ? "1" : "0.6";
          }
        });
      };

      mailDriverSelect.addEventListener("change", toggleSmtpFields);
      toggleSmtpFields(); // Initial call

      // Real-time validation for email fields
      const emailFromField = document.querySelector(
        'input[name="mail_from_address"]'
      );
      if (emailFromField) {
        emailFromField.addEventListener("blur", () => {
          this.validateEmailField(emailFromField);
        });
      }

      const hostField = document.querySelector('input[name="mail_host"]');
      if (hostField) {
        hostField.addEventListener("blur", () => {
          this.validateHostField(hostField);
        });
      }

      const portField = document.querySelector('input[name="mail_port"]');
      if (portField) {
        portField.addEventListener("blur", () => {
          this.validatePortField(portField);
        });
      }
    }
  },

  /**
   * Validate email field
   */
  validateEmailField: function (field) {
    const email = field.value.trim();
    const isValid = email === "" || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    this.setFieldValidation(
      field,
      isValid,
      isValid ? "" : "Địa chỉ email không hợp lệ"
    );
  },

  /**
   * Validate host field
   */
  validateHostField: function (field) {
    const host = field.value.trim();
    const isValid = host === "" || /^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(host);

    this.setFieldValidation(
      field,
      isValid,
      isValid ? "" : "Tên host không hợp lệ (vd: smtp.gmail.com)"
    );
  },

  /**
   * Validate port field
   */
  validatePortField: function (field) {
    const port = parseInt(field.value);
    const isValid = !isNaN(port) && port > 0 && port <= 65535;

    this.setFieldValidation(
      field,
      isValid,
      isValid ? "" : "Port phải là số từ 1 đến 65535"
    );
  },

  /**
   * Set field validation state
   */
  setFieldValidation: function (field, isValid, errorMessage) {
    // Remove existing validation classes
    field.classList.remove("is-valid", "is-invalid");

    // Remove existing feedback
    const existingFeedback = field.parentNode.querySelector(
      ".invalid-feedback, .valid-feedback"
    );
    if (existingFeedback) {
      existingFeedback.remove();
    }

    if (field.value.trim() !== "") {
      if (isValid) {
        field.classList.add("is-valid");
      } else {
        field.classList.add("is-invalid");

        // Add error message
        const feedback = document.createElement("div");
        feedback.className = "invalid-feedback";
        feedback.textContent = errorMessage;
        field.parentNode.appendChild(feedback);
      }
    }
  },

  /**
   * Auto-save settings
   */
  autoSaveSettings: function () {
    const form = document.getElementById("settingsForm");
    if (!form) return;

    const formData = new FormData(form);
    formData.append("action", "auto_save");

    fetch("/admin/ajax/settings-handler", {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          this.showAutoSaveIndicator();
        }
      })
      .catch(error => {
        console.error("Auto-save error:", error);
        this.showToast("Lỗi lưu tự động", "danger");
      });
  },

  /**
   * Update version history display
   */
  updateVersionHistory: function (history) {
    const versionHistory = document.querySelector(".version-history");
    if (!versionHistory || !history || history.length === 0) {
      return;
    }

    let historyHtml = "";
    history.forEach((entry, index) => {
      const isCurrentVersion = index === 0;
      const badgeClass = isCurrentVersion ? "bg-success" : "bg-secondary";
      const versionDate = new Date(entry.date).toLocaleString("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });

      const customIcon = entry.is_custom_description
        ? '<i class="fas fa-pen text-primary ms-1" title="Mô tả tùy chỉnh"></i>'
        : "";

      historyHtml += `
        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
          <div class="flex-grow-1">
            <span class="badge ${badgeClass} me-2">v${entry.version}</span>
            <small class="text-muted">${entry.description}</small>
            ${customIcon}
          </div>
          <div class="d-flex align-items-center">
            <small class="text-muted me-2">${versionDate}</small>
            <button class="btn btn-sm btn-outline-secondary edit-version-btn"
                    data-version="${entry.version}"
                    data-description="${entry.description}"
                    title="Chỉnh sửa mô tả"
                    type="button">
              <i class="fas fa-edit"></i>
            </button>
          </div>
        </div>
      `;
    });

    versionHistory.innerHTML = historyHtml;
  },

  /**
   * Bind edit version events using event delegation
   */
  bindEditVersionEvents: function () {
    // Use specific event listeners instead of global document listener

    // Handle edit version buttons
    document.addEventListener("click", function (e) {
      if (e.target.closest(".edit-version-btn")) {
        e.preventDefault();
        e.stopPropagation();

        const button = e.target.closest(".edit-version-btn");
        const version = button.getAttribute("data-version");
        const description = button.getAttribute("data-description");

        window.Tro365Settings.editVersionDescription(version, description);
      }
    });

    // Handle save version button
    document.addEventListener("click", function (e) {
      if (e.target.closest(".save-version-btn")) {
        e.preventDefault();
        e.stopPropagation();

        window.Tro365Settings.saveVersionDescription();
      }
    });
  },

  /**
   * Bind test email events
   */
  bindTestEmailEvents: function () {
    const testEmailBtn = document.getElementById("test-email-btn");
    if (testEmailBtn) {
      testEmailBtn.addEventListener("click", () => {
        this.testEmailConfig();
      });
    }

    const testSmtpBtn = document.getElementById("test-smtp-connection-btn");
    if (testSmtpBtn) {
      testSmtpBtn.addEventListener("click", () => {
        this.testSmtpConnection();
      });
    }
  },

  /**
   * Edit version description
   */
  editVersionDescription: function (version, currentDescription) {
    // Set modal values
    document.getElementById("editVersionNumber").value = version;
    document.getElementById("editVersionDescription").value =
      currentDescription;

    // Show modal
    const modal = new bootstrap.Modal(
      document.getElementById("editVersionModal")
    );
    modal.show();
  },

  /**
   * Save version description
   */
  saveVersionDescription: function () {
    const version = document.getElementById("editVersionNumber").value;
    const description = document
      .getElementById("editVersionDescription")
      .value.trim();

    if (!description) {
      this.showToast("Vui lòng nhập mô tả", "danger");
      return;
    }

    // Show loading
    const saveBtn = document.querySelector("#editVersionModal .btn-primary");
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';
    saveBtn.disabled = true;

    // Send request
    fetch("/admin/ajax/settings-handler", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: new URLSearchParams({
        action: "edit_version_description",
        version: version,
        description: description,
      }),
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          this.showToast(data.message, "success");

          // Update version history
          if (data.history && data.history.length > 0) {
            this.updateVersionHistory(data.history);
          }

          // Close modal
          const modal = bootstrap.Modal.getInstance(
            document.getElementById("editVersionModal")
          );
          modal.hide();
        } else {
          this.showToast(data.message || "Có lỗi xảy ra", "danger");
        }
      })
      .catch(error => {
        console.error("Error:", error);
        this.showToast("Có lỗi kết nối", "danger");
      })
      .finally(() => {
        // Restore button
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
      });
  },

  /**
   * Show toast notification
   */
  showToast: function (message, type = "info", duration = 3000) {
    if (window.TroToast && typeof window.TroToast.show === "function") {
      window.TroToast.show({ message, type, duration });
      return;
    }
    // Fallback: Bootstrap toast
    const toastContainer =
      document.getElementById("toastContainer") || this.createToastContainer();

    const toastId = "toast_" + Date.now();
    const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

    toastContainer.insertAdjacentHTML("beforeend", toastHtml);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();

    toastElement.addEventListener("hidden.bs.toast", () => {
      toastElement.remove();
    });
  },

  /**
   * Create toast container
   */
  createToastContainer: function () {
    const container = document.createElement("div");
    container.id = "toastContainer";
    container.className = "toast-container position-fixed end-0 p-3";
    container.style.top = "100px";
    container.style.zIndex = "9999";
    document.body.appendChild(container);
    return container;
  },

  /**
   * Show custom confirmation modal
   */
  showConfirmModal: function (options) {
    const {
      title = "Xác nhận",
      message = "Bạn có chắc chắn?",
      confirmText = "Xác nhận",
      cancelText = "Hủy",
      confirmClass = "btn-primary",
      onConfirm = () => {},
      onCancel = () => {},
    } = options;

    // Remove existing modal if any
    const existingModal = document.getElementById("confirmModal");
    if (existingModal) {
      existingModal.remove();
    }

    // Create modal HTML with glassmorphism styling
    const modalHtml = `
      <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 15px;">
            <div class="modal-header border-0">
              <h5 class="modal-title" id="confirmModalLabel">
                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                ${title}
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              ${message}
            </div>
            <div class="modal-footer border-0">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${cancelText}</button>
              <button type="button" class="btn ${confirmClass}" id="confirmModalBtn">${confirmText}</button>
            </div>
          </div>
        </div>
      </div>
    `;

    // Add modal to DOM
    document.body.insertAdjacentHTML("beforeend", modalHtml);

    // Initialize and show modal
    const modalElement = document.getElementById("confirmModal");
    const modal = new bootstrap.Modal(modalElement);

    // Show modal and handle aria-hidden properly
    modal.show();

    // Remove aria-hidden when modal is shown to fix accessibility issue
    modalElement.addEventListener("shown.bs.modal", () => {
      modalElement.removeAttribute("aria-hidden");
    });

    // Handle confirm button
    document.getElementById("confirmModalBtn").addEventListener("click", () => {
      modal.hide();
      onConfirm();
    });

    // Handle modal close (cleanup)
    modalElement.addEventListener("hidden.bs.modal", () => {
      modalElement.remove();
    });
  },

  /**
   * Debounce function
   */
  debounce: function (func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  },

  /**
   * Clear cache and logs
   */
  clearCache: function () {
    // Use custom confirmation modal instead of browser confirm
    this.showConfirmModal({
      title: "Xác nhận xóa Cache & Logs",
      message: `
        <p>Bạn có chắc chắn muốn xóa cache và log files?</p>
        <div class="alert alert-warning mt-3">
          <strong>Hành động này sẽ:</strong>
          <ul class="mb-0 mt-2">
            <li>Xóa tất cả cache hệ thống</li>
            <li>Xóa log files cũ</li>
            <li>Làm trống log files hôm nay</li>
          </ul>
        </div>
      `,
      confirmText: "Xóa Cache",
      confirmClass: "btn-warning",
      onConfirm: () => {
        this.performClearCache();
      },
    });
  },

  /**
   * Perform actual cache clearing
   */
  performClearCache: function () {
    fetch("/admin/cache/clear", {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          let message = "Cache và logs đã được xóa thành công";
          if (data.cleared && data.cleared.length > 0) {
            message += ": " + data.cleared.join(", ");
          }
          this.showToast(message, "success");
        } else {
          this.showToast(
            "Có lỗi xảy ra khi xóa cache: " + (data.message || "Unknown error"),
            "danger"
          );
        }
      })
      .catch(error => {
        console.error("Clear cache error:", error);
        this.showToast("Có lỗi xảy ra khi xóa cache", "danger");
      });
  },

  /**
   * Export settings configuration
   */
  exportSettings: function () {
    // Create JSON export of current settings
    const form = document.getElementById("settingsForm");
    if (!form) {
      this.showToast("Form không tìm thấy", "danger");
      return;
    }

    const formData = new FormData(form);
    const settings = {
      website: {},
      system: {},
      email: {},
      seo: {},
      advanced: {},
      exportTime: new Date().toISOString(),
      version: "N/A", // Will be updated after collecting form data
    };

    // Collect all form data
    for (let [key, value] of formData.entries()) {
      settings.advanced[key] = value;
    }

    // Set version from app_version field if available
    if (settings.advanced.app_version) {
      settings.version = settings.advanced.app_version;
    }

    const dataStr = JSON.stringify(settings, null, 2);
    const dataBlob = new Blob([dataStr], { type: "application/json" });

    const link = document.createElement("a");
    link.href = URL.createObjectURL(dataBlob);
    link.download =
      "tro365-settings-" + new Date().toISOString().split("T")[0] + ".json";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    this.showToast("Đã xuất cấu hình thành công!", "success");
  },

  /**
   * Export system information
   */
  exportSystemInfo: function () {
    const systemInfo = {
      php_version: window.systemData?.php_version || "N/A",
      server: window.systemData?.server || "N/A",
      upload_max_size: window.systemData?.upload_max_size || "N/A",
      memory_limit: window.systemData?.memory_limit || "N/A",
      timestamp: new Date().toISOString(),
    };

    const dataStr = JSON.stringify(systemInfo, null, 2);
    const dataBlob = new Blob([dataStr], { type: "application/json" });

    const link = document.createElement("a");
    link.href = URL.createObjectURL(dataBlob);
    link.download =
      "system-info-" + new Date().toISOString().split("T")[0] + ".json";
    link.click();
  },

  /**
   * Update application version
   */
  updateVersion: function () {
    const version = document.getElementById("app_version").value;
    const description = document.getElementById("version_description").value;

    // Validate version format
    const versionPattern = /^\d+\.\d+\.\d+$/;
    if (!versionPattern.test(version)) {
      this.showToast(
        "Định dạng phiên bản không hợp lệ. Sử dụng định dạng x.y.z (ví dụ: 1.2.3)",
        "danger"
      );
      return;
    }

    // Show loading
    const submitBtn = document.querySelector("#versionForm button");
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';
    submitBtn.disabled = true;

    // Prepare form data
    let formData =
      "action=update_version&app_version=" + encodeURIComponent(version);
    if (description.trim()) {
      formData += "&version_description=" + encodeURIComponent(description);
    }

    fetch("/admin/ajax/settings-handler", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: formData,
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          this.showToast("Phiên bản đã được cập nhật thành công!", "success");

          // Update version display in system info
          const versionBadge = document.querySelector(".badge.bg-primary");
          if (versionBadge) {
            versionBadge.textContent = "v" + version;
          }

          // Update version history
          if (data.history && data.history.length > 0) {
            this.updateVersionHistory(data.history);
          }
        } else {
          this.showToast(
            "Có lỗi khi cập nhật phiên bản: " +
              (data.message || "Unknown error"),
            "danger"
          );
        }
      })
      .catch(error => {
        console.error("Update version error:", error);
        this.showToast("Có lỗi xảy ra khi cập nhật phiên bản", "danger");
      })
      .finally(() => {
        // Restore button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      });

    return false; // Prevent form submission
  },

  /**
   * Test SMTP connection
   */
  testSmtpConnection: function () {
    const form = document.getElementById("settingsForm");
    if (!form) return;

    // Get SMTP configuration from form
    const formData = new FormData(form);
    const smtpData = new URLSearchParams({
      action: "test_smtp_connection",
      mail_host: formData.get("mail_host") || "",
      mail_port: formData.get("mail_port") || "587",
      mail_encryption: formData.get("mail_encryption") || "tls",
      mail_username: formData.get("mail_username") || "",
      mail_password: formData.get("mail_password") || "",
      mail_from_address: formData.get("mail_from_address") || "",
      mail_from_name: formData.get("mail_from_name") || "",
    });

    // Show loading state
    const testBtn = document.getElementById("test-smtp-connection-btn");
    const originalText = testBtn.innerHTML;
    testBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin me-1"></i>Đang test...';
    testBtn.disabled = true;

    // Hide previous result
    const resultDiv = document.getElementById("smtp-connection-result");
    resultDiv.style.display = "none";

    fetch("/admin/ajax/settings-handler", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: smtpData,
    })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        this.showSmtpConnectionResult(data);

        if (data.success) {
          this.showToast("Kết nối SMTP thành công!", "success");
        } else {
          this.showToast("Kết nối SMTP thất bại: " + data.message, "danger");
        }
      })
      .catch(error => {
        console.error("Test SMTP connection error:", error);
        this.showToast(
          "Có lỗi xảy ra khi test kết nối SMTP: " + error.message,
          "danger"
        );
        this.showSmtpConnectionResult({
          success: false,
          message: "Lỗi kết nối: " + error.message,
        });
      })
      .finally(() => {
        // Restore button
        testBtn.innerHTML = originalText;
        testBtn.disabled = false;
      });
  },

  /**
   * Show SMTP connection test result
   */
  showSmtpConnectionResult: function (result) {
    const resultDiv = document.getElementById("smtp-connection-result");
    if (!resultDiv) return;

    let alertClass = result.success ? "alert-success" : "alert-danger";
    let icon = result.success
      ? "fas fa-check-circle"
      : "fas fa-exclamation-circle";

    let html = `
      <div class="alert ${alertClass}">
        <i class="${icon} me-2"></i>
        <strong>${result.success ? "Thành công:" : "Thất bại:"}</strong> ${
      result.message
    }
    `;

    if (result.success && result.host) {
      html += `
        <div class="mt-2">
          <small>
            <strong>Host:</strong> ${result.host}:${result.port}
            <strong>Encryption:</strong> ${result.encryption || "None"}
          </small>
        </div>
      `;
    }

    if (!result.success && result.errors && result.errors.length > 0) {
      html += `
        <div class="mt-2">
          <small><strong>Chi tiết lỗi:</strong></small>
          <ul class="mb-0 mt-1">
      `;
      result.errors.forEach(error => {
        html += `<li><small>${error}</small></li>`;
      });
      html += `</ul></div>`;
    }

    html += `</div>`;

    resultDiv.innerHTML = html;
    resultDiv.style.display = "block";
  },

  /**
   * Test email configuration
   */
  testEmailConfig: function () {
    const testEmailInput = document.getElementById("test-email-input");
    const testEmail = testEmailInput ? testEmailInput.value.trim() : "";

    // Validate email if provided
    if (testEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(testEmail)) {
      this.showToast("Địa chỉ email không hợp lệ!", "danger");
      return;
    }

    // Show loading state
    const testBtn = document.getElementById("test-email-btn");
    const originalText = testBtn.innerHTML;
    testBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin me-1"></i>Đang gửi...';
    testBtn.disabled = true;

    const params = new URLSearchParams({
      action: "test_email",
    });

    if (testEmail) {
      params.append("test_email", testEmail);
    }

    fetch("/admin/ajax/settings-handler", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: params,
    })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          let message = data.message || "Email test đã được gửi thành công!";

          // Show additional config info if available
          if (data.config_info) {
            const configInfo = data.config_info;
            message += `\n\nThông tin cấu hình:\n`;
            message += `• Driver: ${configInfo.driver}\n`;
            if (configInfo.driver === "smtp") {
              message += `• SMTP: ${configInfo.host}:${configInfo.port}\n`;
              message += `• Encryption: ${configInfo.encryption || "None"}\n`;
            }
            message += `• PHPMailer: ${
              configInfo.phpmailer_available ? "Có sẵn" : "Không có"
            }`;
          }

          this.showToast(message, "success");
        } else {
          let errorMessage = "Có lỗi khi gửi email: " + data.message;
          if (data.errors && data.errors.length > 0) {
            errorMessage += "\n\nChi tiết: " + data.errors.join(", ");
          }
          this.showToast(errorMessage, "danger");
        }
      })
      .catch(error => {
        console.error("Test email error:", error);
        this.showToast(
          "Có lỗi xảy ra khi test email: " + error.message,
          "danger"
        );
      })
      .finally(() => {
        // Restore button
        testBtn.innerHTML = originalText;
        testBtn.disabled = false;
      });
  },

  /**
   * Reset settings to default
   */
  resetToDefault: function () {
    if (
      !confirm(
        "Bạn có chắc chắn muốn khôi phục tất cả cài đặt về mặc định? Hành động này không thể hoàn tác!"
      )
    ) {
      return;
    }

    fetch("/admin/reset-settings", {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          this.showToast("Đã khôi phục cài đặt mặc định", "success");
          setTimeout(() => {
            location.reload();
          }, 2000);
        } else {
          this.showToast(
            "Có lỗi khi khôi phục cài đặt: " + data.message,
            "danger"
          );
        }
      })
      .catch(error => {
        console.error("Reset settings error:", error);
        this.showToast("Có lỗi xảy ra khi khôi phục cài đặt", "danger");
      });
  },

  /**
   * Show auto-save indicator
   */
  showAutoSaveIndicator: function () {
    // Remove existing indicator
    const existing = document.querySelector(".auto-save-indicator");
    if (existing) existing.remove();

    const indicator = document.createElement("div");
    indicator.className =
      "auto-save-indicator alert alert-success alert-dismissible fade show position-fixed";
    indicator.style.cssText =
      "top: 20px; right: 20px; z-index: 1050; min-width: 250px;";
    indicator.innerHTML = `
      <i class="fas fa-check-circle me-2"></i>
      Đã lưu tự động
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(indicator);

    setTimeout(() => {
      if (indicator.parentNode) {
        indicator.remove();
      }
    }, 3000);
  },
};

// Auto-initialize on DOM ready
document.addEventListener("DOMContentLoaded", function () {
  console.log("🔧 Initializing Tro365Settings...");
  if (typeof window.Tro365Settings !== "undefined") {
    window.Tro365Settings.init();
    console.log("✅ Tro365Settings initialized successfully");
  } else {
    console.error("❌ Tro365Settings object not found");
  }
});

// Global functions for backward compatibility
function showToast(message, type) {
  return window.Tro365Settings.showToast(message, type);
}

function exportSystemInfo() {
  return window.Tro365Settings.exportSystemInfo();
}

function updateVersion() {
  return window.Tro365Settings.updateVersion();
}

function testEmailConfig() {
  return window.Tro365Settings.testEmailConfig();
}

function resetToDefault() {
  return window.Tro365Settings.resetToDefault();
}

function showAutoSaveIndicator() {
  return window.Tro365Settings.showAutoSaveIndicator();
}

// Edit version functions now handled by event delegation
// No need for global functions

/**
 * Save TinyMCE Settings
 */
function saveTinyMCESettings() {
  const apiKey = document.getElementById("tinymce_api_key").value.trim();

  if (!apiKey) {
    Swal.fire({
      icon: "warning",
      title: "Thiếu API Key",
      text: "Vui lòng nhập TinyMCE API Key!",
    });
    return;
  }

  // Show loading
  Swal.fire({
    title: "Đang lưu cấu hình...",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  // Send AJAX request
  fetch("/admin/ajax/settings-handler", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: `action=save_tinymce&tinymce_api_key=${encodeURIComponent(apiKey)}`,
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        Swal.fire({
          icon: "success",
          title: "Lưu thành công!",
          text: "Cấu hình TinyMCE đã được cập nhật.",
          timer: 2000,
          showConfirmButton: false,
        });
      } else {
        throw new Error(data.message || "Có lỗi xảy ra");
      }
    })
    .catch(error => {
      Swal.fire({
        icon: "error",
        title: "Lỗi!",
        text: error.message || "Không thể lưu cấu hình TinyMCE.",
      });
    });
}

/**
 * Save Room Limit Settings
 */
function saveRoomLimitSettings() {
  const maxRooms = document.getElementById("max_rooms_per_post").value.trim();

  if (
    !maxRooms ||
    isNaN(maxRooms) ||
    parseInt(maxRooms) < 1 ||
    parseInt(maxRooms) > 1000
  ) {
    Swal.fire({
      icon: "warning",
      title: "Giá trị không hợp lệ",
      text: "Số phòng tối đa phải từ 1 đến 1000!",
    });
    return;
  }

  // Show loading
  Swal.fire({
    title: "Đang lưu cấu hình...",
    allowOutsideClick: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  // Send AJAX request
  fetch("/admin/ajax/settings-handler", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      "X-Requested-With": "XMLHttpRequest",
    },
    body: `action=save_room_limit&max_rooms_per_post=${encodeURIComponent(
      maxRooms
    )}`,
  })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        Swal.fire({
          icon: "success",
          title: "Lưu thành công!",
          text: data.message,
          timer: 2000,
          showConfirmButton: false,
        }).then(() => {
          // Reload page to update statistics
          location.reload();
        });
      } else {
        throw new Error(data.message || "Có lỗi xảy ra");
      }
    })
    .catch(error => {
      Swal.fire({
        icon: "error",
        title: "Lỗi!",
        text: error.message || "Không thể lưu cấu hình số phòng.",
      });
    });
}
