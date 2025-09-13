/**
 * Modern Form Validator - Thay thế validation rườm rà
 * Tích hợp với HTML5 validation và custom rules
 */
class FormValidator {
  constructor(form, options = {}) {
    this.form = typeof form === "string" ? document.querySelector(form) : form;
    this.options = {
      showErrors: true,
      realTimeValidation: true,
      errorClass: "is-invalid",
      successClass: "is-valid",
      errorContainer: ".invalid-feedback",
      ...options,
    };

    this.rules = new Map();
    this.errors = new Map();

    // Load validation rules from server before initializing
    this.initAsync();
  }

  /**
   * Async initialization to load validation rules
   */
  async initAsync() {
    try {
      await FormValidator.loadValidationRules();
    } catch (error) {
      console.warn("Failed to load validation rules:", error);
    }

    this.init();
    if (this.form) {
      this.form.dataset.fvInitialized = "1";
    }
  }

  /**
   * Initialize validator
   */
  init() {
    if (!this.form) return;

    // Add form validation class
    this.form.classList.add("needs-validation");
    // Disable HTML5 validation to prevent duplicate messages
    this.form.noValidate = true;

    // Bind events
    this.form.addEventListener("submit", this.handleSubmit.bind(this));

    if (this.options.realTimeValidation) {
      this.form.addEventListener("input", this.handleInput.bind(this));
      this.form.addEventListener("blur", this.handleBlur.bind(this), true);
      this.form.addEventListener("change", this.handleChange.bind(this));
    }

    // Remove any existing HTML5 validation messages
    this.clearNativeValidationMessages();
  }

  /**
   * Add validation rule
   */
  addRule(fieldName, rule, message) {
    if (!this.rules.has(fieldName)) {
      this.rules.set(fieldName, []);
    }
    this.rules.get(fieldName).push({ rule, message });
    return this;
  }

  // Static property to cache validation rules from server
  static validationRules = null;

  /**
   * Load validation rules from server
   */
  static async loadValidationRules() {
    if (FormValidator.validationRules) {
      return FormValidator.validationRules;
    }

    try {
      const response = await fetch("/api/validation");
      if (!response.ok) {
        throw new Error("Failed to load validation rules");
      }
      FormValidator.validationRules = await response.json();
      return FormValidator.validationRules;
    } catch (error) {
      console.warn(
        "Failed to load server validation rules, using fallback:",
        error
      );
      // Fallback to hardcoded rules
      FormValidator.validationRules = {
        patterns: {
          phone:
            "/^(84|0)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-6|8|9]|9[0-4|6-9])[0-9]{7}$/",
          email: "/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/",
          username: "/^[a-zA-Z0-9_]{3,30}$/",
        },
      };
      return FormValidator.validationRules;
    }
  }

  /**
   * Common validation rules
   */
  static rules = {
    required: value => value.trim() !== "",
    email: value => {
      // Use canonical email pattern from server or fallback
      const pattern =
        FormValidator.validationRules?.patterns?.email ||
        "/^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/";
      const regex = new RegExp(pattern.replace(/^\/|\/$/g, ""));
      return regex.test(value);
    },
    // VN phone format - synchronized with server-side validation
    phone: value => {
      // Use canonical phone pattern from server or fallback
      const pattern =
        FormValidator.validationRules?.patterns?.phone ||
        "/^(84|0)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-6|8|9]|9[0-4|6-9])[0-9]{7}$/";
      const regex = new RegExp(pattern.replace(/^\/|\/$/g, ""));
      return regex.test(value.trim());
    },
    minLength: min => value => value.length >= min,
    maxLength: max => value => value.length <= max,
    numeric: value => /^\d+$/.test(value),
    alphanumeric: value => /^[a-zA-Z0-9]+$/.test(value),
    url: value => {
      try {
        new URL(value);
        return true;
      } catch {
        return false;
      }
    },
    match: fieldName => (value, form) => {
      const matchField = form.querySelector(`[name="${fieldName}"]`);
      return matchField ? value === matchField.value : false;
    },
  };

  /**
   * Get Vietnamese validation message based on field validity
   */
  getVietnameseValidationMessage(field) {
    const fieldName = this.getFieldDisplayName(field);

    if (field.validity.valueMissing) {
      // Special handling for checkboxes
      if (field.type === "checkbox") {
        return "Bạn phải đồng ý với điều khoản sử dụng";
      }
      return `Vui lòng nhập ${fieldName}`;
    } else if (field.validity.typeMismatch) {
      if (field.type === "email") {
        return "Vui lòng nhập địa chỉ email hợp lệ";
      } else {
        return `Định dạng ${fieldName} không hợp lệ`;
      }
    } else if (field.validity.patternMismatch) {
      const customMessage = field.getAttribute("data-pattern-message");
      if (customMessage) {
        return customMessage;
      } else {
        return `${fieldName} không đúng định dạng yêu cầu`;
      }
    } else if (field.validity.tooShort) {
      return `${fieldName} phải có ít nhất ${field.minLength} ký tự`;
    } else if (field.validity.tooLong) {
      return `${fieldName} không được vượt quá ${field.maxLength} ký tự`;
    } else if (field.validity.rangeUnderflow) {
      return `${fieldName} phải lớn hơn hoặc bằng ${field.min}`;
    } else if (field.validity.rangeOverflow) {
      return `${fieldName} phải nhỏ hơn hoặc bằng ${field.max}`;
    } else if (field.validity.stepMismatch) {
      return `${fieldName} không hợp lệ`;
    } else {
      return `${fieldName} không hợp lệ`;
    }
  }

  /**
   * Get field display name in Vietnamese
   */
  getFieldDisplayName(field) {
    const fieldNames = {
      username: "tên đăng nhập",
      email: "email",
      fullname: "họ và tên",
      phone: "số điện thoại",
      password: "mật khẩu",
      password_confirmation: "xác nhận mật khẩu",
      password_confirm: "xác nhận mật khẩu",
      address: "địa chỉ",
      birth_date: "ngày sinh",
      title: "tiêu đề",
      description: "mô tả",
      content: "nội dung",
      subject: "chủ đề",
      message: "tin nhắn",
      name: "họ tên",
    };

    return (
      fieldNames[field.name] ||
      fieldNames[field.id] ||
      field.placeholder ||
      "trường này"
    );
  }

  /**
   * Check if this is a form that needs natural submission (auth forms + post creation forms)
   */
  needsNaturalSubmission() {
    // Check form ID, action, or class to identify forms that need natural submission
    const formId = this.form.id;
    const formAction = this.form.action;
    const formClasses = this.form.className;

    // Forms that need natural submission (auth forms + post creation forms)
    const naturalSubmissionIndicators = [
      "loginForm",
      "registerForm",
      "forgotPasswordForm",
      "createPostForm",
      "editPostForm",
      "/login",
      "/register",
      "/forgot-password",
      "/posts/create",
      "/posts/edit",
      "auth-form",
      "login-form",
      "register-form",
      "post-form",
    ];

    return naturalSubmissionIndicators.some(
      indicator =>
        formId.includes(indicator) ||
        formAction.includes(indicator) ||
        formClasses.includes(indicator)
    );
  }

  /**
   * Show validation errors for failed validation
   */
  showValidationErrors() {
    // Show first error field
    const firstErrorField = this.form.querySelector(
      `.${this.options.errorClass}`
    );

    if (firstErrorField) {
      firstErrorField.focus();
      firstErrorField.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    }
  }

  /**
   * Handle form submission
   */
  async handleSubmit(event) {
    // Check if this is a form that needs natural submission
    const needsNatural = this.needsNaturalSubmission();

    if (needsNatural) {
      // For forms that need natural submission, only prevent submission if validation fails
      const isValid = await this.validateForm();

      if (!isValid) {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        // Show validation errors and return
        this.showValidationErrors();
        return;
      }

      // For valid forms that need natural submission, allow natural submission
      // Just add loading state and let browser handle redirect
      const submitBtn = this.form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.classList.add("btn-loading");
        submitBtn.disabled = true;
      }

      // Don't prevent default - let browser handle submission naturally
      return;
    }

    // For forms that don't need natural submission, use original logic
    // Prevent all default form submission behavior including HTML5 validation
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    // Ensure HTML5 validation is completely disabled
    this.form.noValidate = true;

    // Show loading state immediately for better UX
    const submitBtn = this.form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.classList.add("btn-loading");
      submitBtn.disabled = true;

      // Store original text for restoration
      if (!submitBtn.dataset.originalText) {
        submitBtn.dataset.originalText = submitBtn.textContent.trim();
      }
    }

    const isValid = await this.validateForm();

    if (isValid) {
      // Keep loading state for valid forms
      if (submitBtn) {
        // Auto-remove loading after 10 seconds as fallback
        setTimeout(() => {
          submitBtn.classList.remove("btn-loading");
          submitBtn.disabled = false;
        }, 10000);
      }

      // Trigger custom valid event
      this.form.dispatchEvent(
        new CustomEvent("form:valid", {
          detail: { formData: new FormData(this.form) },
        })
      );

      // Submit form if no custom handler
      if (!this.options.preventDefault) {
        this.form.submit();
      }
    } else {
      // Remove loading state for invalid forms with minimum duration
      if (submitBtn) {
        // Ensure loading animation is visible for at least 600ms even for validation errors
        setTimeout(() => {
          submitBtn.classList.remove("btn-loading");
          submitBtn.disabled = false;
        }, 600);
      }

      // Show first error field
      const firstErrorField = this.form.querySelector(
        `.${this.options.errorClass}`
      );
      if (firstErrorField) {
        firstErrorField.focus();
        firstErrorField.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
  }

  /**
   * Handle input events - Enhanced to prevent premature validation
   */
  handleInput(event) {
    if (event.target.matches("input, select, textarea")) {
      const field = event.target;
      // Only validate if field was previously validated or has content
      if (
        field.classList.contains("is-invalid") ||
        field.classList.contains("is-valid") ||
        field.value.trim() !== ""
      ) {
        this.validateField(field);
      }
    }
  }

  /**
   * Handle blur events - Enhanced to validate only after meaningful interaction
   */
  handleBlur(event) {
    if (event.target.matches("input, select, textarea")) {
      const field = event.target;
      // Only validate on blur if field has content or is required and user has interacted
      if (
        field.value.trim() !== "" ||
        (field.hasAttribute("required") &&
          field.dataset.hasInteracted === "true")
      ) {
        this.validateField(field);
      }
      // Mark field as having been interacted with
      field.dataset.hasInteracted = "true";
    }
  }

  /**
   * Handle change events for checkboxes and select elements
   */
  handleChange(event) {
    if (event.target.matches("input[type='checkbox'], select")) {
      const field = event.target;
      this.validateField(field);
    }
  }

  /**
   * Validate entire form
   */
  async validateForm() {
    const fields = this.form.querySelectorAll("input, select, textarea");
    let isValid = true;

    for (const field of fields) {
      const fieldValid = await this.validateField(field);
      if (!fieldValid) isValid = false;
    }

    return isValid;
  }

  /**
   * Validate single field
   */
  async validateField(field) {
    const fieldName = field.name;
    const value = field.value;
    let isValid = true;
    let errorMessage = "";

    // Clear previous errors
    this.clearFieldError(field);

    // Custom validation for checkboxes with data-required attribute
    if (field.type === "checkbox" && field.dataset.required === "true") {
      if (!field.checked) {
        isValid = false;
        errorMessage = "Bạn phải đồng ý với điều khoản sử dụng";
      }
    }
    // HTML5 validation for other fields - use Vietnamese messages
    else if (!field.checkValidity()) {
      isValid = false;
      errorMessage = this.getVietnameseValidationMessage(field);
    }

    // Custom rules
    if (isValid && this.rules.has(fieldName)) {
      const rules = this.rules.get(fieldName);

      for (const { rule, message } of rules) {
        const ruleResult =
          typeof rule === "function"
            ? await rule(value, this.form)
            : FormValidator.rules[rule]?.(value, this.form);

        if (!ruleResult) {
          isValid = false;
          errorMessage = message;
          break;
        }
      }
    }

    // Show error or success
    if (isValid) {
      this.showFieldSuccess(field);
    } else {
      this.showFieldError(field, errorMessage);
    }

    return isValid;
  }

  /**
   * Show field error
   */
  showFieldError(field, message) {
    field.classList.add(this.options.errorClass);
    field.classList.remove(this.options.successClass);

    if (this.options.showErrors) {
      let errorContainer;

      // Special handling for checkbox fields to improve positioning
      if (field.type === "checkbox" && field.id === "terms") {
        // Look for dedicated terms error container
        const termsErrorContainer = field
          .closest(".auth-form-group-enhanced")
          ?.querySelector(".terms-error-container");
        if (termsErrorContainer) {
          errorContainer = termsErrorContainer.querySelector(
            this.options.errorContainer
          );
          if (!errorContainer) {
            errorContainer = document.createElement("div");
            errorContainer.className = this.options.errorContainer.replace(
              ".",
              ""
            );
            termsErrorContainer.appendChild(errorContainer);
          }
        }
      }

      // Fallback to default behavior for other fields
      if (!errorContainer) {
        errorContainer = field.parentNode.querySelector(
          this.options.errorContainer
        );
        if (!errorContainer) {
          errorContainer = document.createElement("div");
          errorContainer.className = this.options.errorContainer.replace(
            ".",
            ""
          );
          field.parentNode.appendChild(errorContainer);
        }
      }

      errorContainer.textContent = message;
      errorContainer.style.display = "block";
    }
  }

  /**
   * Show field success
   */
  showFieldSuccess(field) {
    field.classList.remove(this.options.errorClass);
    field.classList.add(this.options.successClass);
    this.clearFieldError(field);
  }

  /**
   * Clear field error
   */
  clearFieldError(field) {
    let errorContainer;

    // Special handling for checkbox fields
    if (field.type === "checkbox" && field.id === "terms") {
      const termsErrorContainer = field
        .closest(".auth-form-group-enhanced")
        ?.querySelector(".terms-error-container");
      if (termsErrorContainer) {
        errorContainer = termsErrorContainer.querySelector(
          this.options.errorContainer
        );
      }
    }

    // Fallback to default behavior
    if (!errorContainer) {
      errorContainer = field.parentNode.querySelector(
        this.options.errorContainer
      );
    }

    if (errorContainer) {
      errorContainer.style.display = "none";
    }
  }

  /**
   * Clear all loading states
   */
  clearLoadingStates() {
    const loadingButtons = document.querySelectorAll(".btn-loading");
    loadingButtons.forEach(btn => {
      btn.classList.remove("btn-loading");
      btn.disabled = false;
    });
  }

  /**
   * Clear native HTML5 validation messages
   */
  clearNativeValidationMessages() {
    if (!this.form) return;

    const fields = this.form.querySelectorAll("input, select, textarea");
    fields.forEach(field => {
      // Clear custom validity to remove HTML5 messages
      field.setCustomValidity("");

      // Store required state before removing HTML5 validation attributes
      const wasRequired =
        field.hasAttribute("required") || field.dataset.required === "true";

      // Remove HTML5 validation attributes that cause duplicate messages
      field.removeAttribute("required");
      field.removeAttribute("pattern");
      field.removeAttribute("minlength");
      field.removeAttribute("maxlength");
      field.removeAttribute("min");
      field.removeAttribute("max");
      field.removeAttribute("step");

      // Store validation info in data attributes for our custom validation
      if (wasRequired) {
        field.dataset.required = "true";
      }
    });
  }

  /**
   * Reset form validation
   */
  reset() {
    const fields = this.form.querySelectorAll("input, select, textarea");
    fields.forEach(field => {
      field.classList.remove(
        this.options.errorClass,
        this.options.successClass
      );
      this.clearFieldError(field);
    });
    this.errors.clear();
  }
}

// Auto-initialize forms with validation
document.addEventListener("DOMContentLoaded", () => {
  // If ModernApp is present, let it initialize forms to avoid duplicate validators
  if (window.App && window.App instanceof ModernApp) {
    return;
  }
  const forms = document.querySelectorAll(
    "form.needs-validation, form[data-validate]"
  );
  forms.forEach(form => {
    if (!form.dataset.fvInitialized) {
      new FormValidator(form);
    }
  });
});

// Export for global use
window.FormValidator = FormValidator;
