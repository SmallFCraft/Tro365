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
    this.form.noValidate = true;

    // Bind events
    this.form.addEventListener("submit", this.handleSubmit.bind(this));

    if (this.options.realTimeValidation) {
      this.form.addEventListener("input", this.handleInput.bind(this));
      this.form.addEventListener("blur", this.handleBlur.bind(this), true);
    }
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

  /**
   * Common validation rules
   */
  static rules = {
    required: value => value.trim() !== "",
    email: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
    phone: value =>
      /^[0-9+\-\s()]+$/.test(value) && value.replace(/\D/g, "").length >= 10,
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
   * Handle form submission
   */
  async handleSubmit(event) {
    event.preventDefault();

    const isValid = await this.validateForm();

    if (isValid) {
      // Remove loading states from previous validation
      this.clearLoadingStates();

      // Add loading state only if form is valid
      const submitBtn = this.form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.classList.add("btn-loading");
        submitBtn.disabled = true;

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

    // HTML5 validation first
    if (!field.checkValidity()) {
      isValid = false;
      errorMessage = field.validationMessage;
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
      let errorContainer = field.parentNode.querySelector(
        this.options.errorContainer
      );
      if (!errorContainer) {
        errorContainer = document.createElement("div");
        errorContainer.className = this.options.errorContainer.replace(".", "");
        field.parentNode.appendChild(errorContainer);
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
    const errorContainer = field.parentNode.querySelector(
      this.options.errorContainer
    );
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
