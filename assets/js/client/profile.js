/**
 * Profile Page Interactive Features
 * Tro365 - Modern Profile UI with Glass Morphism Design
 * Enhanced UX with animations and real-time features
 */

class ProfileManager {
  constructor() {
    // Use the same localStorage key as global theme system
    this.currentTheme = localStorage.getItem("tro365-theme") || "light";
    this.init();
  }

  init() {
    this.initThemeToggle();
    this.initTabSwitching();
    this.initStatCounters();
    this.initQuickActions();
    this.initAchievements();
    this.initProfileUpdates();
    this.loadUserStats();
    this.initActivityCards();
  }

  /* ===== THEME MANAGEMENT ===== */
  initThemeToggle() {
    // Apply saved theme
    document.documentElement.setAttribute("data-theme", this.currentTheme);

    // Update profile theme toggle to match current theme
    this.updateProfileThemeToggle();

    // Listen for theme changes from header toggle
    document.addEventListener("click", e => {
      if (e.target.closest(".theme-toggle")) {
        // Let header handle the theme change first
        setTimeout(() => {
          // Then sync profile theme toggle
          this.syncThemeFromHeader();
        }, 100);
      }
    });

    // Listen for modern toggle switch changes
    document.addEventListener("change", e => {
      if (e.target.matches('[data-profile-theme-toggle][type="checkbox"]')) {
        const newTheme = e.target.checked ? "dark" : "light";
        this.currentTheme = newTheme;
        document.documentElement.setAttribute("data-theme", this.currentTheme);
        localStorage.setItem("tro365-theme", this.currentTheme);
        this.updateProfileThemeToggle();
      }
    });

    // Listen for theme changes from storage (other tabs)
    window.addEventListener("storage", e => {
      if (e.key === "tro365-theme") {
        this.currentTheme = e.newValue || "light";
        document.documentElement.setAttribute("data-theme", this.currentTheme);
        this.updateProfileThemeToggle();
      }
    });
  }

  syncThemeFromHeader() {
    // Get current theme from document
    const currentTheme =
      document.documentElement.getAttribute("data-theme") || "light";
    this.currentTheme = currentTheme;

    // Update profile theme toggle to match
    this.updateProfileThemeToggle();
  }

  updateProfileThemeToggle() {
    const profileThemeToggle = document.querySelector(
      ".profile-theme-toggle, [data-profile-theme-toggle]"
    );
    if (profileThemeToggle) {
      const icon = profileThemeToggle.querySelector("i");
      const text = profileThemeToggle.querySelector("span");

      if (icon) {
        icon.className = `fas fa-${
          this.currentTheme === "dark" ? "sun" : "moon"
        }`;
      }

      if (text) {
        text.textContent = `${
          this.currentTheme === "dark" ? "Light" : "Dark"
        } Mode`;
      }
    }

    // Update modern toggle switches
    this.updateModernToggleSwitches();
  }

  updateModernToggleSwitches() {
    const themeToggleInputs = document.querySelectorAll(
      "[data-profile-theme-toggle]"
    );
    themeToggleInputs.forEach(input => {
      if (input.type === "checkbox") {
        input.checked = this.currentTheme === "dark";
      }
    });
  }

  /* ===== TAB SWITCHING WITH ANIMATIONS ===== */
  initTabSwitching() {
    const tabButtons = document.querySelectorAll(".profile-nav .nav-link");
    const tabPanes = document.querySelectorAll(".tab-pane");

    tabButtons.forEach(button => {
      button.addEventListener("click", e => {
        e.preventDefault();
        const targetId = button.getAttribute("data-bs-target");
        this.switchTab(targetId, button);
      });
    });

    // Restore active tab from URL hash or localStorage on page load
    this.restoreActiveTab();
  }

  switchTab(targetId, activeButton) {
    // Remove active classes
    document.querySelectorAll(".profile-nav .nav-link").forEach(btn => {
      btn.classList.remove("active");
    });
    document.querySelectorAll(".tab-pane").forEach(pane => {
      pane.classList.remove("show", "active");
    });

    // Add active class to clicked button
    activeButton.classList.add("active");

    // Show target pane with animation
    const targetPane = document.querySelector(targetId);
    if (targetPane) {
      targetPane.style.opacity = "0";
      targetPane.style.transform = "translateY(20px)";
      targetPane.classList.add("show", "active");

      // Animate in
      setTimeout(() => {
        targetPane.style.transition = "all 0.3s ease";
        targetPane.style.opacity = "1";
        targetPane.style.transform = "translateY(0)";
      }, 10);

      // Save active tab to localStorage and URL hash
      this.saveActiveTab(targetId);
    }
  }

  /* ===== TAB STATE MANAGEMENT ===== */
  saveActiveTab(targetId) {
    // Save to localStorage
    localStorage.setItem("profile_active_tab", targetId);

    // Update URL hash without triggering page scroll
    const tabName = targetId.replace("#", "");
    history.replaceState(null, null, `#${tabName}`);
  }

  restoreActiveTab() {
    // Try to get active tab from URL hash first, then localStorage
    let activeTabId = window.location.hash;

    if (!activeTabId) {
      activeTabId = localStorage.getItem("profile_active_tab");
    }

    if (activeTabId) {
      // Ensure the hash starts with #
      if (!activeTabId.startsWith("#")) {
        activeTabId = "#" + activeTabId;
      }

      // Find the corresponding tab button and pane
      const targetPane = document.querySelector(activeTabId);
      const targetButton = document.querySelector(
        `[data-bs-target="${activeTabId}"]`
      );

      if (targetPane && targetButton) {
        // Remove default active states
        document.querySelectorAll(".profile-nav .nav-link").forEach(btn => {
          btn.classList.remove("active");
        });
        document.querySelectorAll(".tab-pane").forEach(pane => {
          pane.classList.remove("show", "active");
        });

        // Activate the saved tab
        targetButton.classList.add("active");
        targetPane.classList.add("show", "active");

        console.log(`Restored active tab: ${activeTabId}`);
      }
    }
  }

  /* ===== ANIMATED STAT COUNTERS ===== */
  initStatCounters() {
    const statCards = document.querySelectorAll(".profile-stat-card");

    const observer = new IntersectionObserver(
      entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.animateStatCounter(entry.target);
          }
        });
      },
      { threshold: 0.5 }
    );

    statCards.forEach(card => observer.observe(card));
  }

  animateStatCounter(card) {
    const numberElement = card.querySelector("h3");
    if (!numberElement || numberElement.dataset.animated) return;

    const finalNumber = parseInt(
      numberElement.textContent.replace(/[^\d]/g, "")
    );
    const duration = 2000;
    const steps = 60;
    const increment = finalNumber / steps;
    let current = 0;

    numberElement.dataset.animated = "true";

    const timer = setInterval(() => {
      current += increment;
      if (current >= finalNumber) {
        current = finalNumber;
        clearInterval(timer);
      }
      numberElement.textContent = this.formatNumber(Math.floor(current));
    }, duration / steps);
  }

  formatNumber(num) {
    if (
      window.TroCurrency &&
      typeof window.TroCurrency.formatNumber === "function"
    ) {
      return window.TroCurrency.formatNumber(num);
    }
    return new Intl.NumberFormat("vi-VN").format(num);
  }

  /* ===== QUICK ACTIONS INTERACTIONS ===== */
  initQuickActions() {
    const quickActions = document.querySelectorAll(".profile-quick-action");

    quickActions.forEach(action => {
      action.addEventListener("mouseenter", () => {
        this.animateQuickAction(action, "enter");
      });

      action.addEventListener("mouseleave", () => {
        this.animateQuickAction(action, "leave");
      });
    });
  }

  animateQuickAction(element, type) {
    const icon = element.querySelector(".profile-quick-action-icon");

    if (type === "enter") {
      icon.style.transform = "scale(1.1) rotate(5deg)";
      element.style.boxShadow = "0 10px 30px rgba(102, 126, 234, 0.2)";
    } else {
      icon.style.transform = "scale(1) rotate(0deg)";
      element.style.boxShadow = "";
    }
  }

  /* ===== ACHIEVEMENT SYSTEM ===== */
  initAchievements() {
    this.checkAchievements();
    this.animateAchievements();
  }

  checkAchievements() {
    // This would typically fetch from API
    const achievements = [
      {
        id: "first_post",
        earned: true,
        title: "Bài đăng đầu tiên",
        icon: "fas fa-star",
      },
      {
        id: "verified_email",
        earned: true,
        title: "Email đã xác thực",
        icon: "fas fa-envelope-check",
      },
      {
        id: "seller_status",
        earned: false,
        title: "Trở thành Seller",
        icon: "fas fa-store",
      },
      {
        id: "popular_post",
        earned: false,
        title: "Bài đăng nổi bật",
        icon: "fas fa-fire",
      },
    ];

    this.renderAchievements(achievements);
  }

  renderAchievements(achievements) {
    const container = document.querySelector(".achievements-grid");
    if (!container) return;

    container.innerHTML = achievements
      .map(
        achievement => `
            <div class="achievement-badge ${
              achievement.earned ? "earned" : ""
            }" 
                 data-achievement="${achievement.id}">
                <div class="achievement-icon">
                    <i class="${achievement.icon}"></i>
                </div>
                <h6 class="achievement-title">${achievement.title}</h6>
            </div>
        `
      )
      .join("");
  }

  animateAchievements() {
    const badges = document.querySelectorAll(".achievement-badge.earned");
    badges.forEach((badge, index) => {
      setTimeout(() => {
        badge.style.animation = "achievementPulse 0.6s ease";
      }, index * 200);
    });
  }

  /* ===== REAL-TIME PROFILE UPDATES ===== */
  initProfileUpdates() {
    // Check for updates every 30 seconds
    setInterval(() => {
      this.checkProfileUpdates();
    }, 30000);
  }

  async checkProfileUpdates() {
    try {
      // This would be an actual API call
      // const response = await fetch('/api/profile/updates');
      // const data = await response.json();

      // Mock update check
      this.updateNotificationBadge();
    } catch (error) {
      console.error("Error checking profile updates:", error);
    }
  }

  updateNotificationBadge() {
    const notificationTab = document.querySelector(
      '[data-bs-target="#notifications"]'
    );
    if (notificationTab && Math.random() > 0.7) {
      let badge = notificationTab.querySelector(".notification-badge");
      if (!badge) {
        badge = document.createElement("span");
        badge.className = "notification-badge";
        badge.style.cssText = `
                    position: absolute;
                    top: -5px;
                    right: -5px;
                    background: #ef4444;
                    color: white;
                    border-radius: 50%;
                    width: 20px;
                    height: 20px;
                    font-size: 0.75rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                `;
        notificationTab.style.position = "relative";
        notificationTab.appendChild(badge);
      }
      badge.textContent = Math.floor(Math.random() * 9) + 1;
    }
  }

  /* ===== LOAD USER STATISTICS ===== */
  async loadUserStats() {
    // Stats are already loaded from PHP and displayed in HTML
    // No need to override with JavaScript mock data
    // Real stats are rendered server-side for better performance
    console.log("User stats loaded from server-side PHP");
  }

  updateStatsDisplay(stats) {
    // Update stat cards with real data
    const statElements = {
      total_posts: stats.totalPosts,
      total_views: stats.totalViews,
      total_likes: stats.totalLikes,
      join_days: stats.joinDays,
    };

    Object.entries(statElements).forEach(([key, value]) => {
      const element = document.querySelector(`[data-stat="${key}"] h3`);
      if (element) {
        element.textContent = this.formatNumber(value);
      }
    });
  }

  /* ===== UTILITY METHODS ===== */
  showToast(message, type = "info", duration = 3000) {
    if (window.TroToast && typeof window.TroToast.show === "function") {
      window.TroToast.show({ message, type, duration });
      return;
    }
    alert(message);
  }

  /* ===== ACTIVITY CARDS INTERACTIONS ===== */
  initActivityCards() {
    const activityCards = document.querySelectorAll(".activity-card");
    const viewAllBtn = document.querySelector(".activity-view-all");

    // Add click interactions to activity cards
    activityCards.forEach((card, index) => {
      // Skip if already initialized
      if (card.dataset.initialized === "true") {
        return;
      }

      // Mark as initialized
      card.dataset.initialized = "true";

      // Add ripple effect on click
      card.addEventListener("click", e => {
        e.stopPropagation();
        this.createRippleEffect(e, card);

        // Optional: Add click action (e.g., show activity details)
        const activityType = card.dataset.activityType;
        // TODO: Implement activity detail modal or navigation
      });

      // Add intersection observer for scroll animations
      if ("IntersectionObserver" in window) {
        const observer = new IntersectionObserver(
          entries => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                entry.target.style.animationDelay = `${index * 0.1}s`;
                entry.target.classList.add("animate-in");
              }
            });
          },
          { threshold: 0.1 }
        );

        observer.observe(card);
      }
    });

    // View all button interaction
    if (viewAllBtn) {
      viewAllBtn.addEventListener("click", () => {
        this.showAllActivities();
      });
    }

    // Add keyboard navigation
    this.initActivityKeyboardNavigation();
  }

  createRippleEffect(event, element) {
    const ripple = document.createElement("div");
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;

    ripple.style.cssText = `
      position: absolute;
      width: ${size}px;
      height: ${size}px;
      left: ${x}px;
      top: ${y}px;
      background: rgba(var(--primary-rgb), 0.3);
      border-radius: 50%;
      transform: scale(0);
      animation: ripple 0.6s ease-out;
      pointer-events: none;
      z-index: 1;
    `;

    element.style.position = "relative";
    element.appendChild(ripple);

    setTimeout(() => {
      ripple.remove();
    }, 600);
  }

  showAllActivities() {
    // Create modal or navigate to full activities page
    this.showToast("Đang tải tất cả hoạt động...", "info");

    // Example: Navigate to activities page
    // window.location.href = '/profile/activities';
  }

  initActivityKeyboardNavigation() {
    const activityCards = document.querySelectorAll(".activity-card");

    activityCards.forEach((card, index) => {
      card.setAttribute("tabindex", "0");
      card.setAttribute("role", "button");
      card.setAttribute("aria-label", `Hoạt động ${index + 1}`);

      card.addEventListener("keydown", e => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          card.click();
        }
      });
    });
  }
}

// CSS for animations
const profileAnimations = `
    @keyframes achievementPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    @keyframes ripple {
        0% {
            transform: scale(0);
            opacity: 1;
        }
        100% {
            transform: scale(2);
            opacity: 0;
        }
    }

    .profile-stat-card h3 {
        transition: all 0.3s ease;
    }

    .tab-pane {
        transition: all 0.3s ease;
    }

    .activity-card.animate-in {
        animation: slideInUp 0.6s ease-out forwards;
    }
`;

// Inject animations CSS
const styleSheet = document.createElement("style");
styleSheet.textContent = profileAnimations;
document.head.appendChild(styleSheet);

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  if (!window.profileManagerInstance) {
    window.profileManagerInstance = new ProfileManager();
  }
});

// Export for use in other modules
if (!window.ProfileManager) {
  window.ProfileManager = ProfileManager;
}
