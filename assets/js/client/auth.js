/**
 * Enhanced Authentication JavaScript - Glass Morphism UI
 * Tro365 - Website thuê trọ
 * Mobile-first responsive design with enhanced UX
 */

window.Tro365Auth = {
  /**
   * Enhanced theme management
   */
  initThemeSupport: function () {
    // Detect system theme preference
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)");

    // Apply saved theme or system preference
    const savedTheme = localStorage.getItem("tro365-theme");
    const theme = savedTheme || (prefersDark.matches ? "dark" : "light");

    document.documentElement.setAttribute("data-theme", theme);

    // Listen for system theme changes
    prefersDark.addEventListener("change", e => {
      if (!localStorage.getItem("tro365-theme")) {
        document.documentElement.setAttribute(
          "data-theme",
          e.matches ? "dark" : "light"
        );
      }
    });
  },

  /**
   * Enhanced mobile detection and optimization
   */
  initMobileOptimizations: function () {
    const isMobile =
      /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
        navigator.userAgent
      );
    const isTouch = "ontouchstart" in window || navigator.maxTouchPoints > 0;

    if (isMobile || isTouch) {
      document.body.classList.add("mobile-device");

      // Optimize viewport for mobile
      const viewport = document.querySelector('meta[name="viewport"]');
      if (viewport) {
        viewport.setAttribute(
          "content",
          "width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"
        );
      }

      // Add touch-friendly classes
      document
        .querySelectorAll(".btn-enhanced, .form-control-enhanced")
        .forEach(el => {
          el.classList.add("touch-friendly");
        });

      // Prevent zoom on input focus for iOS
      document
        .querySelectorAll(
          'input[type="email"], input[type="password"], input[type="text"]'
        )
        .forEach(input => {
          if (
            input.style.fontSize === "" ||
            parseFloat(input.style.fontSize) < 16
          ) {
            input.style.fontSize = "16px";
          }
        });
    }
  },

  /**
   * Enhanced animations and transitions
   */
  initAnimations: function () {
    // Intersection Observer for fade-in animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    };

    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add("animate-in");
        }
      });
    }, observerOptions);

    // Observe elements for animation
    document
      .querySelectorAll(".auth-card-enhanced, .auth-status-enhanced")
      .forEach(el => {
        observer.observe(el);
      });

    // Parallax effect for background
    const authPage = document.querySelector(".auth-page-enhanced");
    if (authPage) {
      window.addEventListener("scroll", () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        const bgElement = authPage.querySelector("::before");
        if (bgElement) {
          authPage.style.setProperty("--scroll-offset", `${rate}px`);
        }
      });
    }
  },

  /**
   * Enhanced accessibility features
   */
  initAccessibility: function () {
    // Enhanced focus management
    document.addEventListener("keydown", function (e) {
      if (e.key === "Tab") {
        document.body.classList.add("keyboard-navigation");
      }
    });

    document.addEventListener("mousedown", function () {
      document.body.classList.remove("keyboard-navigation");
    });

    // ARIA live regions for dynamic content
    const liveRegion = document.createElement("div");
    liveRegion.setAttribute("aria-live", "polite");
    liveRegion.setAttribute("aria-atomic", "true");
    liveRegion.className = "auth-sr-only";
    liveRegion.id = "auth-live-region";
    document.body.appendChild(liveRegion);

    // Announce form validation errors
    document.querySelectorAll("form").forEach(form => {
      form.addEventListener(
        "invalid",
        function (e) {
          const errorMessage =
            e.target.validationMessage ||
            "Vui lòng kiểm tra thông tin nhập vào";
          liveRegion.textContent = errorMessage;
        },
        true
      );
    });
  },

  /**
   * Enhanced performance optimizations
   */
  initPerformanceOptimizations: function () {
    // Lazy load non-critical animations
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

    if (reduceMotion.matches) {
      document.body.classList.add("reduce-motion");

      // Disable animations for users who prefer reduced motion
      const style = document.createElement("style");
      style.textContent = `
        .reduce-motion * {
          animation-duration: 0.01ms !important;
          animation-iteration-count: 1 !important;
          transition-duration: 0.01ms !important;
        }
      `;
      document.head.appendChild(style);
    }

    // Optimize backdrop-filter for better performance
    const supportsBackdropFilter = CSS.supports("backdrop-filter", "blur(1px)");
    if (!supportsBackdropFilter) {
      document.body.classList.add("no-backdrop-filter");
    }

    // Debounced resize handler
    let resizeTimeout;
    window.addEventListener("resize", () => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        this.handleResize();
      }, 250);
    });
  },

  /**
   * Handle responsive layout changes
   */
  handleResize: function () {
    const width = window.innerWidth;

    // Update mobile classes
    if (width <= 768) {
      document.body.classList.add("mobile-layout");
      document.body.classList.remove("desktop-layout");
    } else {
      document.body.classList.add("desktop-layout");
      document.body.classList.remove("mobile-layout");
    }

    // Adjust glass morphism effects based on screen size
    if (width <= 480) {
      document.body.classList.add("small-mobile");
    } else {
      document.body.classList.remove("small-mobile");
    }
  },
  /**
   * Initialize form validation
   */
  initFormValidation: function () {
    const forms = document.querySelectorAll(".needs-validation");

    forms.forEach(form => {
      form.addEventListener("submit", function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }

        form.classList.add("was-validated");
      });

      // Set custom Vietnamese validation messages
      this.setValidationMessages(form);

      // Real-time validation
      const inputs = form.querySelectorAll("input, select, textarea");
      inputs.forEach(input => {
        // Only validate after user interaction
        input.addEventListener("blur", function () {
          this.classList.add("was-validated");
          this.validateField();
        });

        input.addEventListener("input", function () {
          if (this.classList.contains("was-validated")) {
            this.validateField();
          }
        });

        // Add validateField method to each input
        input.validateField = function () {
          const isValid = this.checkValidity();
          const feedbacks =
            this.parentNode.querySelectorAll(".invalid-feedback");

          if (isValid) {
            this.classList.remove("is-invalid");
            this.classList.add("is-valid");
            // Clear all feedback messages
            feedbacks.forEach(feedback => (feedback.textContent = ""));
          } else {
            this.classList.remove("is-valid");
            this.classList.add("is-invalid");
            // Only show message in the first feedback element to avoid duplicates
            if (feedbacks.length > 0) {
              const primaryFeedback = feedbacks[0];
              primaryFeedback.textContent =
                this.validationMessage ||
                this.getAttribute("title") ||
                "Vui lòng nhập thông tin hợp lệ";

              // Clear other feedback elements
              for (let i = 1; i < feedbacks.length; i++) {
                feedbacks[i].textContent = "";
              }
            }
          }
        };
      });
    });
  },

  /**
   * Enhanced password confirmation validation
   */
  initPasswordConfirmation: function () {
    const passwordField = document.getElementById("password");
    const confirmField = document.getElementById("password_confirm");
    const confirmFeedback = document.getElementById("confirmPasswordFeedback");

    if (!passwordField || !confirmField) return;

    const validateConfirmation = () => {
      const password = passwordField.value;
      const confirmPassword = confirmField.value;

      // Only validate if user has interacted with the field
      if (!confirmField.classList.contains("was-validated")) {
        return;
      }

      if (confirmPassword === "") {
        confirmField.classList.remove("is-valid", "is-invalid");
        if (confirmFeedback) confirmFeedback.textContent = "";
        confirmField.setCustomValidity("");
        return;
      }

      if (password === confirmPassword) {
        confirmField.classList.remove("is-invalid");
        confirmField.classList.add("is-valid");
        if (confirmFeedback) {
          confirmFeedback.textContent = "Mật khẩu khớp";
          confirmFeedback.className = "valid-feedback";
        }
        confirmField.setCustomValidity("");
      } else {
        confirmField.classList.remove("is-valid");
        confirmField.classList.add("is-invalid");
        if (confirmFeedback) {
          confirmFeedback.textContent = "Mật khẩu xác nhận không khớp";
          confirmFeedback.className = "invalid-feedback";
        }
        confirmField.setCustomValidity("Mật khẩu xác nhận không khớp");
      }
    };

    confirmField.addEventListener("blur", function () {
      this.classList.add("was-validated");
    });

    confirmField.addEventListener("input", validateConfirmation);
    passwordField.addEventListener("input", validateConfirmation);
  },

  /**
   * Enhanced password strength checker
   */
  initPasswordStrength: function () {
    const passwordField = document.getElementById("password");
    const strengthBar = document.getElementById("passwordStrength");
    const strengthText = document.getElementById("passwordStrengthText");

    if (!passwordField || !strengthBar) return;

    passwordField.addEventListener("input", function () {
      const password = this.value;

      if (password === "") {
        strengthBar.className = "password-strength";
        if (strengthText) strengthText.textContent = "";
        return;
      }

      let strength = 0;
      let feedback = [];

      // Length check
      if (password.length >= 8) {
        strength++;
      } else {
        feedback.push("ít nhất 8 ký tự");
      }

      // Lowercase check
      if (password.match(/[a-z]/)) {
        strength++;
      } else {
        feedback.push("chữ thường");
      }

      // Uppercase check
      if (password.match(/[A-Z]/)) {
        strength++;
      } else {
        feedback.push("chữ hoa");
      }

      // Number check
      if (password.match(/[0-9]/)) {
        strength++;
      } else {
        feedback.push("số");
      }

      // Special character check
      if (password.match(/[^a-zA-Z0-9]/)) {
        strength++;
      } else {
        feedback.push("ký tự đặc biệt");
      }

      // Update strength bar
      strengthBar.className = "password-strength";
      let strengthLevel = "";
      let strengthColor = "";

      if (strength <= 2) {
        strengthLevel = "Yếu";
        strengthColor = "weak";
        strengthBar.classList.add("strength-weak");
      } else if (strength <= 3) {
        strengthLevel = "Trung bình";
        strengthColor = "medium";
        strengthBar.classList.add("strength-medium");
      } else if (strength <= 4) {
        strengthLevel = "Mạnh";
        strengthColor = "strong";
        strengthBar.classList.add("strength-strong");
      } else {
        strengthLevel = "Rất mạnh";
        strengthColor = "strong";
        strengthBar.classList.add("strength-strong");
      }

      if (strengthText) {
        strengthText.className = `password-strength-text ${strengthColor}`;
        if (feedback.length > 0) {
          strengthText.textContent = `${strengthLevel} - Cần: ${feedback.join(
            ", "
          )}`;
        } else {
          strengthText.textContent = `${strengthLevel}`;
        }
      }
    });
  },

  /**
   * Username availability checker
   */
  initUsernameChecker: function () {
    const usernameField = document.getElementById("username");
    const usernameFeedback = document.getElementById("usernameFeedback");

    if (!usernameField) return;

    let checkTimeout;

    usernameField.addEventListener("input", function () {
      const username = this.value.trim();

      clearTimeout(checkTimeout);

      if (username.length < 3) {
        this.classList.remove("is-valid", "is-invalid");
        if (usernameFeedback) usernameFeedback.textContent = "";
        return;
      }

      checkTimeout = setTimeout(() => {
        fetch("/api/check-availability", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "",
          },
          body: JSON.stringify({ type: "username", value: username }),
        })
          .then(response => response.json())
          .then(data => {
            if (data.available) {
              usernameField.classList.remove("is-invalid");
              usernameField.classList.add("is-valid");
              if (usernameFeedback) {
                usernameFeedback.textContent =
                  data.message || "Tên đăng nhập có thể sử dụng";
                usernameFeedback.className = "valid-feedback";
              }
            } else {
              usernameField.classList.remove("is-valid");
              usernameField.classList.add("is-invalid");
              if (usernameFeedback) {
                usernameFeedback.textContent =
                  data.message || "Tên đăng nhập đã tồn tại";
                usernameFeedback.className = "invalid-feedback";
              }
            }
          })
          .catch(error => {
            console.error("Error checking username:", error);
          });
      }, 500);
    });
  },

  /**
   * Email validation
   */
  initEmailValidation: function () {
    const emailField = document.getElementById("email");
    const emailFeedback = document.getElementById("emailFeedback");

    if (!emailField) return;

    let checkTimeout;

    emailField.addEventListener("input", function () {
      const email = this.value.trim();

      clearTimeout(checkTimeout);

      // Basic email format validation
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (email === "") {
        this.classList.remove("is-valid", "is-invalid");
        if (emailFeedback) emailFeedback.textContent = "";
        return;
      }

      if (!emailRegex.test(email)) {
        this.classList.remove("is-valid");
        this.classList.add("is-invalid");
        if (emailFeedback) {
          emailFeedback.textContent = "Định dạng email không hợp lệ";
          emailFeedback.className = "invalid-feedback";
        }
        return;
      }

      checkTimeout = setTimeout(() => {
        fetch("/api/check-availability", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "",
          },
          body: JSON.stringify({ type: "email", value: email }),
        })
          .then(response => response.json())
          .then(data => {
            if (data.available) {
              emailField.classList.remove("is-invalid");
              emailField.classList.add("is-valid");
              if (emailFeedback) {
                emailFeedback.textContent =
                  data.message || "Email có thể sử dụng";
                emailFeedback.className = "valid-feedback";
              }
            } else {
              emailField.classList.remove("is-valid");
              emailField.classList.add("is-invalid");
              if (emailFeedback) {
                emailFeedback.textContent =
                  data.message || "Email đã được sử dụng";
                emailFeedback.className = "invalid-feedback";
              }
            }
          })
          .catch(error => {
            console.error("Error checking email:", error);
          });
      }, 500);
    });
  },

  /**
   * Form submission with loading state
   */
  initFormSubmission: function () {
    const forms = document.querySelectorAll("form");

    forms.forEach(form => {
      form.addEventListener("submit", function () {
        // Only add loading state if form is valid
        if (this.checkValidity()) {
          const submitBtn = this.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.classList.add("btn-loading");
            submitBtn.disabled = true;

            // Re-enable after 5 seconds as fallback
            setTimeout(() => {
              submitBtn.classList.remove("btn-loading");
              submitBtn.disabled = false;
            }, 5000);
          }
        }
      });
    });
  },

  /**
   * Set Vietnamese validation messages for form inputs
   */
  setValidationMessages: function (form) {
    const inputs = form.querySelectorAll("input, select, textarea");

    inputs.forEach(input => {
      const fieldName = this.getFieldDisplayName(input);

      // Set custom validation messages
      input.addEventListener("invalid", function () {
        if (this.validity.valueMissing) {
          this.setCustomValidity(`Vui lòng nhập ${fieldName}`);
        } else if (this.validity.typeMismatch) {
          if (this.type === "email") {
            this.setCustomValidity("Vui lòng nhập địa chỉ email hợp lệ");
          } else {
            this.setCustomValidity(`Định dạng ${fieldName} không hợp lệ`);
          }
        } else if (this.validity.patternMismatch) {
          const customMessage = this.getAttribute("data-pattern-message");
          if (customMessage) {
            this.setCustomValidity(customMessage);
          } else {
            this.setCustomValidity(`${fieldName} không đúng định dạng yêu cầu`);
          }
        } else if (this.validity.tooShort) {
          this.setCustomValidity(
            `${fieldName} phải có ít nhất ${this.minLength} ký tự`
          );
        } else if (this.validity.tooLong) {
          this.setCustomValidity(
            `${fieldName} không được vượt quá ${this.maxLength} ký tự`
          );
        } else {
          this.setCustomValidity(`${fieldName} không hợp lệ`);
        }
      });

      // Clear custom validity on input
      input.addEventListener("input", function () {
        this.setCustomValidity("");
      });
    });
  },

  /**
   * Get display name for form field
   */
  getFieldDisplayName: function (input) {
    const fieldNames = {
      username: "Tên đăng nhập",
      email: "Email",
      fullname: "Họ và tên",
      phone: "Số điện thoại",
      password: "Mật khẩu",
      password_confirm: "Xác nhận mật khẩu",
      address: "Địa chỉ",
      birth_date: "Ngày sinh",
      gender: "Giới tính",
      cccd: "Số CCCD",
    };

    return fieldNames[input.name] || fieldNames[input.id] || "thông tin";
  },

  /**
   * Initialize all enhanced authentication features
   */
  init: function () {
    // Enhanced features first
    this.initThemeSupport();
    this.initMobileOptimizations();
    this.initAnimations();
    this.initAccessibility();
    this.initPerformanceOptimizations();

    // Original features
    this.initFormValidation();
    this.initPasswordConfirmation();
    this.initPasswordStrength();
    this.initUsernameChecker();
    this.initEmailValidation();
    this.initFormSubmission();

    // Initial resize handling
    this.handleResize();

    console.log("Tro365 Enhanced Auth JavaScript initialized");
  },
};

// Auto-initialize on DOM ready
document.addEventListener("DOMContentLoaded", function () {
  window.Tro365Auth.init();
});
