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
      const authStyle = document.createElement("style");
      authStyle.textContent = `
        .reduce-motion * {
          animation-duration: 0.01ms !important;
          animation-iteration-count: 1 !important;
          transition-duration: 0.01ms !important;
        }
      `;
      document.head.appendChild(authStyle);
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
   * Utilities: feedback ARIA and status indicator
   */
  ensureFeedbackARIA: function (el) {
    if (!el) return;
    el.setAttribute("role", "status");
    el.setAttribute("aria-live", "polite");
  },
  setupStatusIndicator: function (input, idSuffix) {
    const group =
      input.closest(".auth-form-group-enhanced") || input.parentElement;
    const id = `${input.id || input.name || "field"}${idSuffix || "Status"}`;
    let statusEl = document.getElementById(id);
    if (!statusEl) {
      statusEl = document.createElement("div");
      statusEl.id = id;
      statusEl.className = "input-status-indicator";
      group && group.appendChild(statusEl);
    }
    return function setIndicator(state) {
      statusEl.className = `input-status-indicator ${state}`;
      if (state === "checking") {
        statusEl.innerHTML = '<span class="spinner"></span>';
      } else {
        statusEl.innerHTML = "";
      }
    };
  },

  /**
   * Common validators for phone and CCCD across pages
   */
  initCommonFieldValidation: function () {
    // Phone fields
    const phoneSelectors = ["#phone", "#contact_phone"];
    phoneSelectors.forEach(sel => {
      const field = document.querySelector(sel);
      const feedback = field
        ? field.nextElementSibling &&
          field.nextElementSibling.classList &&
          field.nextElementSibling.classList.contains("invalid-feedback")
          ? field.nextElementSibling
          : document.getElementById(field.id + "Feedback")
        : null;
      if (!field) return;
      this.ensureFeedbackARIA(feedback);
      const setIndicator = this.setupStatusIndicator(field);
      const vnPhone =
        /^(84|0)(3[2-9]|5[6|8|9]|7[06-9]|8[1-689]|9[0-46-9])[0-9]{7}$/;
      const onValidate = () => {
        const v = field.value.trim();
        if (!v) {
          field.classList.remove("is-valid", "is-invalid");
          feedback && (feedback.textContent = "");
          setIndicator("idle");
          return;
        }
        if (vnPhone.test(v)) {
          field.classList.remove("is-invalid");
          field.classList.add("is-valid");
          if (feedback) {
            feedback.innerHTML =
              '<i class="fas fa-check-circle"></i> Số điện thoại hợp lệ';
            feedback.className = "valid-feedback show";
          }
          setIndicator("valid");
        } else {
          field.classList.remove("is-valid");
          field.classList.add("is-invalid");
          if (feedback) {
            feedback.innerHTML =
              '<i class="fas fa-exclamation-circle"></i> Số điện thoại không hợp lệ';
            feedback.className = "invalid-feedback show";
          }
          setIndicator("invalid");
        }
      };
      field.addEventListener("input", onValidate);
      field.addEventListener("blur", onValidate);
    });

    // CCCD fields
    const cccd = document.getElementById("cccd");
    if (cccd) {
      const feedback = document.getElementById("cccdFeedback");
      this.ensureFeedbackARIA(feedback);
      const setIndicator = this.setupStatusIndicator(cccd);
      const onValidate = () => {
        const v = cccd.value.trim();
        if (!v) {
          cccd.classList.remove("is-valid", "is-invalid");
          feedback && (feedback.textContent = "");
          setIndicator("idle");
          return;
        }
        if (/^[0-9]{9,12}$/.test(v)) {
          cccd.classList.remove("is-invalid");
          cccd.classList.add("is-valid");
          if (feedback) {
            feedback.innerHTML =
              '<i class="fas fa-check-circle"></i> CCCD hợp lệ';
            feedback.className = "valid-feedback show";
          }
          setIndicator("idle");
        } else {
          cccd.classList.remove("is-valid");
          cccd.classList.add("is-invalid");
          if (feedback) {
            feedback.innerHTML =
              '<i class="fas fa-exclamation-circle"></i> CCCD phải có từ 9-12 chữ số';
            feedback.className = "invalid-feedback show";
          }
          setIndicator("idle");
        }
      };
      cccd.addEventListener("input", onValidate);
      cccd.addEventListener("blur", onValidate);
    }
  },

  /**
   * Login page realtime validation
   */
  initLoginRealtime: function () {
    const form = document.getElementById("loginForm");
    if (!form) return;
    const user = document.getElementById("username");
    const pass = document.getElementById("password");
    if (user) {
      const feedback =
        user.parentElement.querySelector(".invalid-feedback") ||
        document.getElementById("usernameFeedback");
      this.ensureFeedbackARIA(feedback);
      const setIndicator = this.setupStatusIndicator(user);
      const validate = () => {
        const v = user.value.trim();
        if (!v) {
          user.classList.remove("is-valid");
          user.classList.add("is-invalid");
          feedback &&
            (feedback.innerHTML =
              '<i class="fas fa-exclamation-circle"></i> Vui lòng nhập tên đăng nhập hoặc email');
          feedback && (feedback.className = "invalid-feedback show");
          setIndicator("invalid");
          return;
        }
        if (v.includes("@")) {
          const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
          user.classList.toggle("is-invalid", !ok);
          user.classList.toggle("is-valid", ok);
          if (feedback) {
            feedback.innerHTML = ok
              ? '<i class="fas fa-check-circle"></i> Email hợp lệ'
              : '<i class="fas fa-exclamation-circle"></i> Email không hợp lệ';
            feedback.className = (ok ? "valid" : "invalid") + "-feedback show";
          }
          setIndicator("idle");
        } else {
          // Username pattern
          const ok = /^[A-Za-z0-9_]{3,30}$/.test(v);
          user.classList.toggle("is-invalid", !ok);
          user.classList.toggle("is-valid", ok);
          if (feedback) {
            feedback.innerHTML = ok
              ? '<i class="fas fa-check-circle"></i> Tên đăng nhập hợp lệ'
              : '<i class="fas fa-exclamation-circle"></i> 3-30 ký tự, chữ/số/gạch dưới';
            feedback.className = (ok ? "valid" : "invalid") + "-feedback show";
          }
          setIndicator("idle");
        }
      };
      user.addEventListener("input", validate);
      user.addEventListener("blur", validate);
    }
    if (pass) {
      const feedback = pass.parentElement.querySelector(".invalid-feedback");
      this.ensureFeedbackARIA(feedback);
      const setIndicator = this.setupStatusIndicator(pass);
      const validate = () => {
        const ok = pass.value.length >= 8;
        pass.classList.toggle("is-invalid", !ok);
        pass.classList.toggle("is-valid", ok);
        if (feedback) {
          feedback.innerHTML = ok
            ? '<i class="fas fa-check-circle"></i> Mật khẩu hợp lệ'
            : '<i class="fas fa-exclamation-circle"></i> Tối thiểu 8 ký tự';
          feedback.className = (ok ? "valid" : "invalid") + "-feedback show";
        }
        setIndicator(ok ? "valid" : "invalid");
      };
      pass.addEventListener("input", validate);
      pass.addEventListener("blur", validate);
    }
  },

  /**
   * Initialize form validation - Enhanced to work with FormValidator
   */
  initFormValidation: function () {
    const forms = document.querySelectorAll(".needs-validation");

    forms.forEach(form => {
      // Skip if FormValidator is already handling this form
      if (form.dataset.fvInitialized === "1") {
        console.log(
          "FormValidator detected, skipping auth.js validation for form:",
          form.id
        );
        // Disable HTML5 validation to prevent duplicate messages
        form.noValidate = true;
        return;
      }

      // Disable HTML5 validation to prevent duplicate messages
      form.noValidate = true;

      form.addEventListener("submit", function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }

        form.classList.add("was-validated");
      });

      // Set custom Vietnamese validation messages
      this.setValidationMessages(form);

      // Enhanced real-time validation - only after meaningful interaction
      const inputs = form.querySelectorAll("input, select, textarea");
      inputs.forEach(input => {
        let hasInteracted = false;

        // Track meaningful interaction (typing or focus loss with content)
        input.addEventListener("input", function () {
          hasInteracted = true;
          if (this.classList.contains("was-validated")) {
            this.validateField();
          }
        });

        // Only validate on blur if user has actually interacted and field has content or is required
        input.addEventListener("blur", function () {
          if (
            hasInteracted ||
            (this.value.trim() !== "" && this.hasAttribute("required"))
          ) {
            this.classList.add("was-validated");
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

    // Helper: escape HTML for safe text injection
    const escapeHTML = str =>
      (str || "").replace(
        /[&<>"']/g,
        c =>
          ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;",
          }[c])
      );

    // Ensure status indicator exists (spinner/check/cross)
    const group =
      usernameField.closest(".auth-form-group-enhanced") ||
      usernameField.parentElement;
    let statusEl = document.getElementById("usernameStatus");
    if (!statusEl) {
      statusEl = document.createElement("div");
      statusEl.id = "usernameStatus";
      statusEl.className = "input-status-indicator";
      group.appendChild(statusEl);
    }

    const setIndicator = state => {
      statusEl.className = `input-status-indicator ${state}`; // states: checking|valid|invalid|idle
      if (state === "checking") {
        statusEl.innerHTML = '<span class="spinner"></span>';
      } else if (state === "valid") {
        statusEl.innerHTML = '<i class="fas fa-check-circle"></i>';
      } else if (state === "invalid") {
        statusEl.innerHTML = '<i class="fas fa-times-circle"></i>';
      } else {
        statusEl.innerHTML = "";
      }
    };

    let checkTimeout;

    const runAvailabilityCheck = username => {
      if (username.length < 3) {
        usernameField.classList.remove("is-valid", "is-invalid");
        if (usernameFeedback) {
          usernameFeedback.textContent = "";
          usernameFeedback.classList.remove("show");
        }
        setIndicator("idle");
        return;
      }

      setIndicator("checking");
      if (usernameFeedback) {
        usernameFeedback.innerHTML =
          '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';
        usernameFeedback.className = "info-feedback show";
      }

      fetch("/api/check-availability", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": (function () {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.getAttribute("content") : "";
          })(),
        },
        body: JSON.stringify({ type: "username", value: username }),
      })
        .then(response => response.json())
        .then(data => {
          if (data.available) {
            usernameField.classList.remove("is-invalid");
            usernameField.classList.add("is-valid");
            setIndicator("idle");
            if (usernameFeedback) {
              const msg = escapeHTML(
                data.message || "Tên đăng nhập có thể sử dụng"
              );
              usernameFeedback.innerHTML = `<i class=\"fas fa-check-circle\"></i> ${msg}`;
              usernameFeedback.className = "valid-feedback show";
            }
          } else {
            usernameField.classList.remove("is-valid");
            usernameField.classList.add("is-invalid");
            setIndicator("idle");
            if (usernameFeedback) {
              const msg = escapeHTML(
                data.message || "Tên đăng nhập đã tồn tại"
              );
              usernameFeedback.innerHTML = `<i class=\"fas fa-times-circle\"></i> ${msg}`;
              usernameFeedback.className = "invalid-feedback show";
            }
          }
        })
        .catch(error => {
          console.error("Error checking username:", error);
          usernameField.classList.remove("is-valid");
          usernameField.classList.add("is-invalid");
          setIndicator("invalid");
          if (usernameFeedback) {
            usernameFeedback.innerHTML = `<i class=\"fas fa-exclamation-circle\"></i> Không thể kiểm tra tên đăng nhập. Vui lòng thử lại.`;
            usernameFeedback.className = "invalid-feedback show";
          }
        });
    };

    usernameField.addEventListener("input", function () {
      const username = this.value.trim();
      clearTimeout(checkTimeout);
      // Show checking indicator quickly for better UX
      setIndicator(username.length >= 3 ? "checking" : "idle");
      checkTimeout = setTimeout(() => runAvailabilityCheck(username), 350);
    });

    usernameField.addEventListener("blur", function () {
      runAvailabilityCheck(this.value.trim());
    });
  },

  /**
   * Email validation for registration
   */
  initEmailValidation: function () {
    const emailField = document.getElementById("email");
    const emailFeedback = document.getElementById("emailFeedback");

    if (!emailField) return;

    let checkTimeout;

    // Set up status indicator
    const group =
      emailField.closest(".auth-form-group-enhanced") ||
      emailField.parentElement;
    let statusEl = document.getElementById("emailStatus");
    if (!statusEl) {
      statusEl = document.createElement("div");
      statusEl.id = "emailStatus";
      statusEl.className = "input-status-indicator";
      group.appendChild(statusEl);
    }
    const setIndicator = state => {
      statusEl.className = `input-status-indicator ${state}`;
      if (state === "checking")
        statusEl.innerHTML = '<span class="spinner"></span>';
      else if (state === "valid")
        statusEl.innerHTML = '<i class="fas fa-check-circle"></i>';
      else if (state === "invalid")
        statusEl.innerHTML = '<i class="fas fa-times-circle"></i>';
      else statusEl.innerHTML = "";
    };

    const runEmailCheck = email => {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (email === "") {
        emailField.classList.remove("is-valid", "is-invalid");
        if (emailFeedback) {
          emailFeedback.textContent = "";
          emailFeedback.classList.remove("show");
        }
        setIndicator("idle");
        return;
      }
      if (!emailRegex.test(email)) {
        emailField.classList.remove("is-valid");
        emailField.classList.add("is-invalid");
        setIndicator("invalid");
        if (emailFeedback) {
          emailFeedback.innerHTML =
            '<i class="fas fa-exclamation-circle"></i> Định dạng email không hợp lệ';
          emailFeedback.className = "invalid-feedback show";
        }
        return;
      }

      setIndicator("checking");
      if (emailFeedback) {
        emailFeedback.innerHTML =
          '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';
        emailFeedback.className = "info-feedback show";
      }

      fetch("/api/check-availability", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": (function () {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.getAttribute("content") : "";
          })(),
        },
        body: JSON.stringify({ type: "email", value: email }),
      })
        .then(response => response.json())
        .then(data => {
          if (data.available) {
            emailField.classList.remove("is-invalid");
            emailField.classList.add("is-valid");
            setIndicator("idle");
            if (emailFeedback) {
              emailFeedback.innerHTML = `<i class=\"fas fa-check-circle\"></i> ${
                data.message || "Email có thể sử dụng"
              }`;
              emailFeedback.className = "valid-feedback show";
            }
          } else {
            emailField.classList.remove("is-valid");
            emailField.classList.add("is-invalid");
            setIndicator("idle");
            if (emailFeedback) {
              emailFeedback.innerHTML = `<i class=\"fas fa-times-circle\"></i> ${
                data.message || "Email đã được sử dụng"
              }`;
              emailFeedback.className = "invalid-feedback show";
            }
          }
        })
        .catch(error => {
          console.error("Error checking email:", error);
          emailField.classList.remove("is-valid");
          emailField.classList.add("is-invalid");
          setIndicator("invalid");
          if (emailFeedback) {
            emailFeedback.innerHTML = `<i class=\"fas fa-exclamation-circle\"></i> Không thể kiểm tra email. Vui lòng thử lại.`;
            emailFeedback.className = "invalid-feedback show";
          }
        });
    };

    emailField.addEventListener("input", function () {
      const email = this.value.trim();
      clearTimeout(checkTimeout);
      setIndicator(email.length ? "checking" : "idle");
      checkTimeout = setTimeout(() => runEmailCheck(email), 350);
    });

    emailField.addEventListener("blur", function () {
      runEmailCheck(this.value.trim());
    });
  },

  /**
   * Email validation for forgot password (opposite logic of registration)
   */
  initForgotPasswordEmailValidation: function () {
    const emailField = document.getElementById("email");
    const emailFeedback = document.getElementById("emailFeedback");

    if (!emailField) return;

    let checkTimeout;

    // Set up status indicator
    const group =
      emailField.closest(".auth-form-group-enhanced") ||
      emailField.parentElement;
    let statusEl = document.getElementById("emailStatus");
    if (!statusEl) {
      statusEl = document.createElement("div");
      statusEl.id = "emailStatus";
      statusEl.className = "input-status-indicator";
      group.appendChild(statusEl);
    }
    const setIndicator = state => {
      statusEl.className = `input-status-indicator ${state}`;
      if (state === "checking")
        statusEl.innerHTML = '<span class="spinner"></span>';
      else if (state === "valid")
        statusEl.innerHTML = '<i class="fas fa-check-circle"></i>';
      else if (state === "invalid")
        statusEl.innerHTML = '<i class="fas fa-times-circle"></i>';
      else statusEl.innerHTML = "";
    };

    const runEmailCheck = email => {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (email === "") {
        emailField.classList.remove("is-valid", "is-invalid");
        if (emailFeedback) {
          emailFeedback.textContent = "";
          emailFeedback.classList.remove("show");
        }
        setIndicator("idle");
        return;
      }
      if (!emailRegex.test(email)) {
        emailField.classList.remove("is-valid");
        emailField.classList.add("is-invalid");
        setIndicator("invalid");
        if (emailFeedback) {
          emailFeedback.innerHTML =
            '<i class="fas fa-exclamation-circle"></i> Định dạng email không hợp lệ';
          emailFeedback.className = "invalid-feedback show";
        }
        return;
      }

      setIndicator("checking");
      if (emailFeedback) {
        emailFeedback.innerHTML =
          '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';
        emailFeedback.className = "info-feedback show";
      }

      fetch("/api/check-availability", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": (function () {
            var m = document.querySelector('meta[name="csrf-token"]');
            return m ? m.getAttribute("content") : "";
          })(),
        },
        body: JSON.stringify({ type: "email", value: email }),
      })
        .then(response => response.json())
        .then(data => {
          // OPPOSITE logic for forgot password: available=false means email exists (GOOD)
          if (!data.available) {
            emailField.classList.remove("is-invalid");
            emailField.classList.add("is-valid");
            setIndicator("idle");
            if (emailFeedback) {
              emailFeedback.innerHTML = `<i class=\"fas fa-check-circle\"></i> Email hợp lệ`;
              emailFeedback.className = "valid-feedback show";
            }
          } else {
            emailField.classList.remove("is-valid");
            emailField.classList.add("is-invalid");
            setIndicator("idle");
            if (emailFeedback) {
              emailFeedback.innerHTML = `<i class=\"fas fa-times-circle\"></i> Email không tồn tại trong hệ thống`;
              emailFeedback.className = "invalid-feedback show";
            }
          }
        })
        .catch(error => {
          console.error("Email validation error:", error);
          emailField.classList.remove("is-valid", "is-invalid");
          setIndicator("idle");
          if (emailFeedback) {
            emailFeedback.innerHTML =
              '<i class="fas fa-exclamation-triangle"></i> Lỗi kiểm tra email';
            emailFeedback.className = "invalid-feedback show";
          }
        });
    };

    emailField.addEventListener("input", function () {
      const email = this.value.trim();
      clearTimeout(checkTimeout);
      setIndicator(email.length ? "checking" : "idle");
      checkTimeout = setTimeout(() => runEmailCheck(email), 350);
    });

    emailField.addEventListener("blur", function () {
      runEmailCheck(this.value.trim());
    });
  },

  /**
   * Form submission with loading state
   */
  initFormSubmission: function () {
    const forms = document.querySelectorAll("form");

    forms.forEach(form => {
      // Skip forms already handled by FormValidator
      if (form.dataset.fvInitialized === "1") {
        return;
      }

      form.addEventListener("submit", function (event) {
        // Prevent double submission
        if (this.dataset.submitting === "true") {
          event.preventDefault();
          return false;
        }

        // Show loading state immediately for better UX
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
          submitBtn.classList.add("btn-loading");
          submitBtn.disabled = true;
        }

        // Check form validity
        if (this.checkValidity()) {
          // Mark as submitting for valid forms
          this.dataset.submitting = "true";

          // Re-enable after 10 seconds as fallback
          setTimeout(() => {
            if (submitBtn) {
              submitBtn.classList.remove("btn-loading");
              submitBtn.disabled = false;
            }
            this.dataset.submitting = "false";
          }, 10000);
        } else {
          // Remove loading state for invalid forms
          if (submitBtn) {
            submitBtn.classList.remove("btn-loading");
            submitBtn.disabled = false;
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

    // Skip registration email validation on forgot password page
    if (!window.location.pathname.includes("forgot-password")) {
      this.initEmailValidation();
    }

    this.initCommonFieldValidation();
    this.initLoginRealtime();
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
