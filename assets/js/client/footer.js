/**
 * Footer JavaScript
 * Tro365 - Website thuê trọ
 */

window.Tro365Footer = {
  /**
   * Initialize footer functionality
   */
  init: function () {
    this.initBackToTop();
    this.initQuickSearch();
    this.initScrollAnimations();

    if (window.TRO365_DEBUG) {
      console.log("Tro365 Footer JavaScript initialized");
    }
  },

  /**
   * Back to top functionality
   */
  initBackToTop: function () {
    const backToTopBtn = document.getElementById("backToTop");
    if (!backToTopBtn) return;

    // Show/hide button based on scroll position with throttling to reduce forced reflow
    let ticking = false;
    const toggleBackToTop = () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          // Use scrollY instead of pageYOffset for better performance
          if (window.scrollY > 300) {
            backToTopBtn.classList.add("show");
          } else {
            backToTopBtn.classList.remove("show");
          }
          ticking = false;
        });
        ticking = true;
      }
    };

    // Enhanced smooth scroll to top with progress indicator
    const scrollToTop = () => {
      const startPosition = window.scrollY;
      const startTime = performance.now();
      const duration = 1000; // 1 second duration

      // Easing function for smooth animation
      const easeInOutCubic = t => {
        return t < 0.5
          ? 4 * t * t * t
          : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1;
      };

      const animateScroll = currentTime => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        const easedProgress = easeInOutCubic(progress);
        const currentPosition = startPosition * (1 - easedProgress);

        window.scrollTo(0, currentPosition);

        if (progress < 1) {
          requestAnimationFrame(animateScroll);
        }
      };

      requestAnimationFrame(animateScroll);
    };

    // Event listeners
    window.addEventListener("scroll", toggleBackToTop);
    backToTopBtn.addEventListener("click", scrollToTop);

    // Initial check
    toggleBackToTop();
  },

  /**
   * Quick search modal
   */
  initQuickSearch: function () {
    const quickSearchBtn = document.getElementById("quickSearch");
    const quickSearchModal = document.getElementById("quickSearchModal");
    const quickSearchForm = document.getElementById("quickSearchForm");

    if (!quickSearchBtn || !quickSearchModal) return;

    // Open advanced search modal instead of quick search modal
    quickSearchBtn.addEventListener("click", () => {
      // Check if modern navigation is available and use its openSearch method
      if (
        window.modernNav &&
        typeof window.modernNav.openSearch === "function"
      ) {
        window.modernNav.openSearch();
      } else {
        // Fallback to opening the search overlay directly
        const searchOverlay = document.getElementById("searchOverlay");
        if (searchOverlay) {
          searchOverlay.classList.add("active");
          document.body.style.overflow = "hidden";

          // Focus on search input
          const searchInput = searchOverlay.querySelector(".search-input");
          if (searchInput) {
            setTimeout(() => searchInput.focus(), 300);
          }
        }
      }
    });

    // Handle form submission
    if (quickSearchForm) {
      quickSearchForm.addEventListener("submit", e => {
        e.preventDefault();

        const formData = new FormData(quickSearchForm);
        const params = new URLSearchParams();

        // Map form fields to search parameters
        for (let [key, value] of formData.entries()) {
          if (value.trim()) {
            // Map keyword to search parameter
            if (key === "keyword") {
              params.append("search", value.trim());
            } else {
              params.append(key, value.trim());
            }
          }
        }

        // Show loading state on submit button
        const submitBtn = quickSearchForm.querySelector(
          'button[type="submit"]'
        );
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML =
          '<i class="fas fa-spinner fa-spin me-2"></i>Đang tìm...';
        submitBtn.disabled = true;

        // Add a small delay for better UX
        setTimeout(() => {
          // Redirect to search page with parameters
          window.location.href = `/search?${params.toString()}`;
        }, 500);
      });
    }
  },

  /**
   * Scroll animations
   */
  initScrollAnimations: function () {
    // Intersection Observer for fade-in animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    };

    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = "1";
          entry.target.style.transform = "translateY(0)";
        }
      });
    }, observerOptions);

    // Observe footer sections
    const footerSections = document.querySelectorAll(".footer-section");
    footerSections.forEach(section => {
      section.style.opacity = "0";
      section.style.transform = "translateY(30px)";
      section.style.transition = "opacity 0.6s ease, transform 0.6s ease";
      observer.observe(section);
    });
  },

  /**
   * Show notification (unified)
   */
  showNotification: function (message, type = "info", duration = 3000) {
    if (window.TroToast && typeof window.TroToast.show === "function") {
      window.TroToast.show({ message, type, duration });
      return;
    }
    // Fallback: Bootstrap alert
    const notification = document.createElement("div");
    notification.className = `alert alert-${
      type === "error" ? "danger" : type
    } alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
    notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
    document.body.appendChild(notification);
    setTimeout(() => {
      if (notification.parentNode) {
        const alert = bootstrap.Alert.getOrCreateInstance(notification);
        alert.close();
      }
    }, 5000);
  },

  /**
   * Smooth scroll to element
   */
  scrollToElement: function (elementId) {
    const element = document.getElementById(elementId);
    if (element) {
      element.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  },
};

// Auto-initialize on DOM ready
document.addEventListener("DOMContentLoaded", function () {
  window.Tro365Footer.init();
});

// Export for global access
window.Footer = window.Tro365Footer;
