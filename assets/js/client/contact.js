/**
 * Contact Page Interactive Features
 * Tro365 - Website thuê trọ
 * Modern 2025-2026 Interactive Components
 */

class ContactPageManager {
  constructor() {
    this.init();
  }

  init() {
    this.initFAQ();
    this.initFormValidation();
    this.initAnimations();
    this.initSmoothScrolling();
    this.initContactCards();
    console.log("Contact Page Manager initialized");
  }

  /**
   * Initialize FAQ Accordion Functionality
   */
  initFAQ() {
    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach(item => {
      const header = item.querySelector(".faq-header");

      header.addEventListener("click", () => {
        const isActive = item.classList.contains("active");

        // Close all other FAQ items
        faqItems.forEach(otherItem => {
          if (otherItem !== item) {
            otherItem.classList.remove("active");
          }
        });

        // Toggle current item
        if (isActive) {
          item.classList.remove("active");
        } else {
          item.classList.add("active");
        }
      });
    });
  }

  /**
   * Initialize Form Enhancement - Validation handled by FormValidator
   */
  initFormValidation() {
    const form = document.getElementById("contactForm");
    if (!form) return;

    // Ensure form has proper validation class for FormValidator
    form.classList.add("needs-validation");

    // Add floating label effect
    this.initFloatingLabels();
  }

  // Field validation handled by FormValidator (standardized)

  /**
   * Show field error
   */
  showFieldError(field, message) {
    field.classList.add("error");

    // Remove existing error message
    const existingError = field.parentNode.querySelector(".field-error");
    if (existingError) {
      existingError.remove();
    }

    // Add new error message
    const errorElement = document.createElement("div");
    errorElement.className = "field-error";
    errorElement.textContent = message;
    errorElement.style.cssText = `
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            animation: slideInUp 0.3s ease;
        `;

    field.parentNode.appendChild(errorElement);
  }

  /**
   * Clear field error
   */
  clearFieldError(field) {
    field.classList.remove("error");
    const errorElement = field.parentNode.querySelector(".field-error");
    if (errorElement) {
      errorElement.remove();
    }
  }

  /**
   * Show form submission state
   */
  showSubmissionState(button, isSubmitting) {
    if (isSubmitting) {
      button.disabled = true;
      button.innerHTML =
        '<i class="fas fa-spinner fa-spin me-2"></i>Đang gửi...';
    } else {
      button.disabled = false;
      button.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Gửi tin nhắn';
    }
  }

  /**
   * Initialize floating label effects
   */
  initFloatingLabels() {
    const formGroups = document.querySelectorAll(".contact-form-group");

    formGroups.forEach(group => {
      const input = group.querySelector("input, textarea");
      const label = group.querySelector("label");

      if (!input || !label) return;

      // Add floating label class
      group.classList.add("floating-label");

      // Check initial state
      if (input.value.trim()) {
        group.classList.add("has-value");
      }

      // Add event listeners
      input.addEventListener("focus", () => {
        group.classList.add("focused");
        input.style.transform = "translateY(-1px)";
      });

      input.addEventListener("blur", () => {
        group.classList.remove("focused");
        input.style.transform = "translateY(0)";
        if (input.value.trim()) {
          group.classList.add("has-value");
        } else {
          group.classList.remove("has-value");
        }
      });

      // Add input animation
      input.addEventListener("input", () => {
        if (input.value.trim()) {
          group.classList.add("has-value");
        } else {
          group.classList.remove("has-value");
        }
      });
    });
  }

  /**
   * Initialize scroll animations
   */
  initAnimations() {
    const observerOptions = {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    };

    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0)";

          // Add staggered animation for grid items
          if (entry.target.classList.contains("contact-info-card")) {
            const cards =
              entry.target.parentNode.querySelectorAll(".contact-info-card");
            cards.forEach((card, index) => {
              setTimeout(() => {
                card.style.opacity = "1";
                card.style.transform = "translateY(0)";
              }, index * 100);
            });
          }
        }
      });
    }, observerOptions);

    // Observe animated elements
    const animatedElements = document.querySelectorAll(
      ".contact-info-card, .contact-form-container, .faq-item, .map-container"
    );

    animatedElements.forEach(el => {
      el.style.opacity = "0";
      el.style.transform = "translateY(30px)";
      el.style.transition = "all 0.6s ease-out";
      observer.observe(el);
    });
  }

  /**
   * Initialize smooth scrolling
   */
  initSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener("click", function (e) {
        e.preventDefault();
        const href = this.getAttribute("href");
        // Skip empty or invalid selectors
        if (!href || href === "#" || href.length <= 1) {
          return;
        }
        try {
          const target = document.querySelector(href);
          if (target) {
            target.scrollIntoView({
              behavior: "smooth",
              block: "start",
            });
          }
        } catch (error) {
          if (window.TRO365_DEBUG) {
            console.warn("Invalid selector for smooth scrolling:", href, error);
          }
        }
      });
    });
  }

  /**
   * Initialize contact card interactions
   */
  initContactCards() {
    const cards = document.querySelectorAll(".contact-info-card");

    cards.forEach(card => {
      // Add click to copy functionality for phone and email
      const phoneLink = card.querySelector('a[href^="tel:"]');
      const emailLink = card.querySelector('a[href^="mailto:"]');

      if (phoneLink) {
        this.addCopyToClipboard(phoneLink, "Số điện thoại đã được sao chép!");
      }

      if (emailLink) {
        this.addCopyToClipboard(emailLink, "Email đã được sao chép!");
      }

      // Add hover sound effect (optional)
      card.addEventListener("mouseenter", () => {
        card.style.transform = "translateY(-10px) scale(1.02)";
      });

      card.addEventListener("mouseleave", () => {
        card.style.transform = "translateY(0) scale(1)";
      });
    });
  }

  /**
   * Add copy to clipboard functionality
   */
  addCopyToClipboard(element, successMessage) {
    element.addEventListener("click", async e => {
      e.preventDefault();

      let textToCopy = "";
      if (element.href.startsWith("tel:")) {
        textToCopy = element.href.replace("tel:", "");
      } else if (element.href.startsWith("mailto:")) {
        textToCopy = element.href.replace("mailto:", "");
      }

      try {
        await navigator.clipboard.writeText(textToCopy);
        this.showToast(successMessage, "success");
      } catch (err) {
        console.error("Failed to copy: ", err);
        this.showToast("Không thể sao chép. Vui lòng thử lại.", "error");
      }
    });
  }

  /**
   * Show toast notification (unified)
   */
  showToast(message, type = "info", duration = 3000) {
    if (window.TroToast && typeof window.TroToast.show === "function") {
      window.TroToast.show({ message, type, duration });
      return;
    }
    alert(message);
  }
}

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  new ContactPageManager();
});

// Add CSS animations for toast
const contactStyle = document.createElement("style");
contactStyle.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }

    .floating-label {
        position: relative;
    }

    .floating-label label {
        position: absolute;
        top: 0.875rem;
        left: 1rem;
        transition: all 0.3s ease;
        pointer-events: none;
        color: var(--text-muted);
    }

    .floating-label.focused label,
    .floating-label.has-value label {
        top: -0.5rem;
        left: 0.75rem;
        font-size: 0.875rem;
        color: var(--primary-color);
        background: var(--bg-primary);
        padding: 0 0.5rem;
        border-radius: 4px;
    }

    .contact-form-input.error,
    .contact-form-textarea.error {
        border-color: var(--danger-color);
        box-shadow: 0 0 0 3px rgba(var(--danger-rgb), 0.1);
    }
`;
document.head.appendChild(contactStyle);
