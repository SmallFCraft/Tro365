/**
 * Mobile Navigation for Admin Panel
 * Tro365 - Website thuê trọ
 * Mobile-first responsive navigation with Glass Morphism UI
 */

class AdminMobileNav {
  constructor() {
    this.sidebar = null;
    this.overlay = null;
    this.toggleBtn = null;
    this.isOpen = false;

    this.init();
  }

  init() {
    this.createMobileElements();
    this.bindEvents();
    this.handleResize();
  }

  createMobileElements() {
    // Use existing Bootstrap navbar-toggler instead of creating new button
    this.toggleBtn = document.querySelector(".navbar-toggler");
    if (this.toggleBtn) {
      // Update classes to match our mobile navigation
      this.toggleBtn.classList.add("mobile-menu-toggle");

      // Disable Bootstrap collapse behavior to avoid conflicts
      this.toggleBtn.removeAttribute("data-bs-toggle");
      this.toggleBtn.removeAttribute("data-bs-target");
      this.toggleBtn.removeAttribute("aria-controls");
    }

    // Ensure Bootstrap navbar collapse is always hidden on mobile
    const navbarCollapse = document.querySelector("#adminNavbar");
    if (navbarCollapse) {
      navbarCollapse.classList.remove("show");
      navbarCollapse.style.display = "none";
    }

    // Create sidebar overlay
    if (!document.querySelector(".sidebar-overlay")) {
      const overlay = document.createElement("div");
      overlay.className = "sidebar-overlay";
      document.body.appendChild(overlay);
      this.overlay = overlay;
    }

    // Find or create sidebar
    this.sidebar = document.querySelector(".admin-sidebar");
    if (!this.sidebar) {
      // Create sidebar from existing navigation
      this.createSidebarFromNav();
    }
  }

  createSidebarFromNav() {
    const navItems = document.querySelectorAll(".navbar-nav .nav-item");
    if (navItems.length === 0) return;

    const sidebar = document.createElement("div");
    sidebar.className = "admin-sidebar";

    const sidebarContent = document.createElement("div");
    sidebarContent.className = "sidebar-content";

    // Add close button
    const closeBtn = document.createElement("button");
    closeBtn.className = "btn-close sidebar-close mb-3";
    closeBtn.setAttribute("aria-label", "Close navigation");
    sidebarContent.appendChild(closeBtn);

    // Add brand
    const brand = document.createElement("div");
    brand.className = "sidebar-brand mb-4";
    brand.innerHTML = `
            <i class="fas fa-shield-alt me-2"></i>
            <strong>${
              document.querySelector(".navbar-brand")?.textContent || "Admin"
            }</strong>
        `;
    sidebarContent.appendChild(brand);

    // Add navigation items
    const navList = document.createElement("ul");
    navList.className = "sidebar-nav list-unstyled";

    navItems.forEach(item => {
      const link = item.querySelector(".nav-link");
      if (link) {
        const listItem = document.createElement("li");
        listItem.className = "sidebar-nav-item mb-2";

        const sidebarLink = document.createElement("a");
        sidebarLink.href = link.href;
        sidebarLink.className = `sidebar-nav-link ${
          link.classList.contains("active") ? "active" : ""
        }`;
        sidebarLink.innerHTML = link.innerHTML;

        listItem.appendChild(sidebarLink);
        navList.appendChild(listItem);
      }
    });

    sidebarContent.appendChild(navList);
    sidebar.appendChild(sidebarContent);
    document.body.appendChild(sidebar);

    this.sidebar = sidebar;
  }

  bindEvents() {
    // Toggle button click
    if (this.toggleBtn) {
      this.toggleBtn.addEventListener("click", e => {
        e.preventDefault();
        this.toggle();
      });
    }

    // Overlay click
    if (this.overlay) {
      this.overlay.addEventListener("click", () => {
        this.close();
      });
    }

    // Close button click
    const closeBtn = document.querySelector(".sidebar-close");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => {
        this.close();
      });
    }

    // Escape key
    document.addEventListener("keydown", e => {
      if (e.key === "Escape" && this.isOpen) {
        this.close();
      }
    });

    // Window resize
    window.addEventListener("resize", () => {
      this.handleResize();
    });

    // Sidebar link clicks
    if (this.sidebar) {
      this.sidebar.addEventListener("click", e => {
        if (e.target.matches(".sidebar-nav-link")) {
          this.close();
        }
      });
    }
  }

  toggle() {
    if (this.isOpen) {
      this.close();
    } else {
      this.open();
    }
  }

  open() {
    if (!this.sidebar || !this.overlay) return;

    this.sidebar.classList.add("show");
    this.overlay.classList.add("show");
    this.isOpen = true;

    // Prevent body scroll
    document.body.style.overflow = "hidden";

    // Focus management
    const firstLink = this.sidebar.querySelector(".sidebar-nav-link");
    if (firstLink) {
      setTimeout(() => firstLink.focus(), 100);
    }
  }

  close() {
    if (!this.sidebar || !this.overlay) return;

    this.sidebar.classList.remove("show");
    this.overlay.classList.remove("show");
    this.isOpen = false;

    // Restore body scroll
    document.body.style.overflow = "";

    // Return focus to toggle button
    if (this.toggleBtn) {
      this.toggleBtn.focus();
    }
  }

  handleResize() {
    const navbarCollapse = document.querySelector("#adminNavbar");

    if (window.innerWidth >= 768) {
      // Desktop: Show Bootstrap navbar, hide custom sidebar
      if (navbarCollapse) {
        navbarCollapse.style.display = "";
        navbarCollapse.classList.add("show");
      }

      // Close custom sidebar if open
      if (this.isOpen) {
        this.close();
      }
    } else {
      // Mobile: Hide Bootstrap navbar, use custom sidebar
      if (navbarCollapse) {
        navbarCollapse.classList.remove("show");
        navbarCollapse.style.display = "none";
      }
    }
  }
}

// Initialize mobile navigation when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  // Only initialize on admin pages
  if (
    document.body.classList.contains("admin-page") ||
    document.querySelector(".navbar") ||
    window.location.pathname.includes("/admin")
  ) {
    new AdminMobileNav();
    console.log("✅ Admin Mobile Navigation initialized");
  }
});

// Export for manual initialization if needed
window.AdminMobileNav = AdminMobileNav;
