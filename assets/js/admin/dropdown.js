/**
 * Universal Bootstrap Dropdown Manager
 * Tro365 - Website thuê trọ
 *
 * Handles dropdown functionality across all layouts (client, admin, seller)
 * Automatically detects layout type and applies appropriate logic
 */

window.Tro365UniversalDropdownManager =
  window.Tro365UniversalDropdownManager || {
    initialized: false,
    dropdownInstances: new Map(),
    layoutType: null,

    /**
     * Auto-detect layout type based on URL and DOM
     */
    detectLayoutType: function () {
      if (this.layoutType) return this.layoutType;

      const currentPath = window.location.pathname;

      // Detect layout type by URL pattern
      if (currentPath.startsWith("/admin/") || currentPath === "/admin") {
        this.layoutType = "admin";
      } else if (currentPath.startsWith("/seller/")) {
        this.layoutType = "seller";
      } else {
        this.layoutType = "client";
      }

      console.log("Detected layout type:", this.layoutType);
      return this.layoutType;
    },

    /**
     * Get layout-specific data attribute name
     */
    getDataAttribute: function () {
      const layout = this.detectLayoutType();
      return `data-${layout}-dropdown-initialized`;
    },

    /**
     * Get layout-specific log prefix
     */
    getLogPrefix: function () {
      const layout = this.detectLayoutType();
      return layout.charAt(0).toUpperCase() + layout.slice(1);
    },

    /**
     * Initialize dropdown manager
     */
    init: function () {
      if (this.initialized) {
        console.log(
          `${this.getLogPrefix()} dropdown manager already initialized`
        );
        return;
      }

      try {
        // Detect layout type
        this.detectLayoutType();

        // Initialize existing dropdowns with direct event binding
        this.initializeDropdowns();

        this.initialized = true;
        console.log(
          `${this.getLogPrefix()} dropdown manager initialized successfully`
        );
      } catch (error) {
        console.error(
          `Failed to initialize ${this.getLogPrefix().toLowerCase()} dropdown manager:`,
          error
        );
      }
    },

    /**
     * Handle dropdown click events
     */
    handleDropdownClick: function (event) {
      // Check if the clicked element or its parent is a dropdown toggle
      const dropdownToggle =
        event.target.closest('[data-bs-toggle="dropdown"]') ||
        (event.target.hasAttribute &&
        event.target.hasAttribute("data-bs-toggle") &&
        event.target.getAttribute("data-bs-toggle") === "dropdown"
          ? event.target
          : null);

      if (!dropdownToggle) return;

      // Don't prevent default - let Bootstrap handle the dropdown
      // event.preventDefault();
      // event.stopPropagation();

      try {
        // Special handling for settings page - always recreate instance
        const isSettingsPage =
          window.location.pathname.includes("/admin/settings");

        // Get or create dropdown instance
        let dropdown = this.dropdownInstances.get(dropdownToggle);

        if (isSettingsPage && dropdown) {
          // For settings page, dispose and recreate instance to avoid conflicts
          try {
            dropdown.dispose();
          } catch (e) {
            console.warn("Failed to dispose existing dropdown:", e);
          }
          this.dropdownInstances.delete(dropdownToggle);
          dropdown = null;
        }

        if (!dropdown) {
          // Use viewport boundary for both admin and client to avoid clipping issues
          const boundary = "viewport";
          dropdown = new bootstrap.Dropdown(dropdownToggle, {
            autoClose: true,
            boundary,
          });
          this.dropdownInstances.set(dropdownToggle, dropdown);
          console.log(
            `${this.getLogPrefix()} dropdown instance created for:`,
            dropdownToggle
          );
        }

        // Close other dropdowns first
        this.closeOtherDropdowns(dropdownToggle);

        // Toggle current dropdown
        dropdown.toggle();
      } catch (error) {
        console.warn(
          `Failed to handle ${this.getLogPrefix().toLowerCase()} dropdown click:`,
          error
        );
      }
    },

    /**
     * Close other open dropdowns
     */
    closeOtherDropdowns: function (currentToggle) {
      this.dropdownInstances.forEach((dropdown, toggle) => {
        if (
          toggle !== currentToggle &&
          toggle.getAttribute("aria-expanded") === "true"
        ) {
          dropdown.hide();
        }
      });
    },

    /**
     * Initialize all dropdown elements
     */
    initializeDropdowns: function () {
      const dataAttribute = this.getDataAttribute();
      const dropdownElements = document.querySelectorAll(
        '[data-bs-toggle="dropdown"]'
      );

      dropdownElements.forEach(dropdownToggle => {
        try {
          // Initialize if not already bound or if marker exists without an instance
          if (
            dropdownToggle.hasAttribute(dataAttribute) &&
            this.dropdownInstances.has(dropdownToggle)
          ) {
            return;
          }
          if (
            dropdownToggle.hasAttribute(dataAttribute) &&
            !this.dropdownInstances.has(dropdownToggle)
          ) {
            // Clean up stale marker to rebind properly
            dropdownToggle.removeAttribute(dataAttribute);
          }

          // Ensure proper Bootstrap attributes
          if (!dropdownToggle.hasAttribute("role")) {
            dropdownToggle.setAttribute("role", "button");
          }
          if (!dropdownToggle.hasAttribute("aria-expanded")) {
            dropdownToggle.setAttribute("aria-expanded", "false");
          }

          // Bind click event directly (bubble phase to work with Bootstrap)
          dropdownToggle.addEventListener(
            "click",
            event => {
              this.handleDropdownClick(event);
            },
            false
          );

          // Mark as initialized
          dropdownToggle.setAttribute(dataAttribute, "true");

          console.log(
            `${this.getLogPrefix()} dropdown initialized with direct binding:`,
            dropdownToggle
          );
        } catch (error) {
          console.warn(
            `Failed to initialize ${this.getLogPrefix().toLowerCase()} dropdown:`,
            error
          );
        }
      });
    },

    /**
     * Force re-initialization of all dropdowns
     */
    forceReinit: function () {
      try {
        // Clear existing instances
        this.dropdownInstances.clear();

        // Remove existing initialization markers
        const dataAttribute = this.getDataAttribute();
        const dropdownElements = document.querySelectorAll(
          `[${dataAttribute}]`
        );
        dropdownElements.forEach(element => {
          element.removeAttribute(dataAttribute);
        });

        // Re-initialize all dropdowns
        this.initializeDropdowns();

        console.log(
          `${this.getLogPrefix()} dropdowns force re-initialized for page conflicts`
        );
      } catch (error) {
        console.warn(
          `Failed to force re-initialize ${this.getLogPrefix().toLowerCase()} dropdowns:`,
          error
        );
      }
    },

    /**
     * Check if current page needs force re-initialization
     */
    shouldForceReinit: function () {
      const currentPath = window.location.pathname;
      const layout = this.detectLayoutType();

      switch (layout) {
        case "admin":
          // Re-init on all admin pages except settings (which has special recreate logic)
          return (
            currentPath.startsWith("/admin") &&
            !currentPath.includes("/admin/settings")
          );
        case "client":
          // Exclude profile/edit page from force reinit to avoid dropdown conflicts
          if (currentPath === "/profile/edit") {
            return false;
          }
          return (
            !currentPath.startsWith("/admin/") &&
            !currentPath.startsWith("/seller/") &&
            !currentPath.startsWith("/api/")
          );
        case "seller":
          return currentPath.startsWith("/seller/");
        default:
          return false;
      }
    },

    /**
     * Auto-initialize with force re-init if needed
     */
    autoInit: function () {
      // Initialize dropdown manager
      this.init();

      // Force re-initialization after page scripts load if needed
      setTimeout(() => {
        if (this.shouldForceReinit()) {
          console.log(
            `Force re-initializing ${this.getLogPrefix().toLowerCase()} dropdowns for page conflicts:`,
            window.location.pathname
          );
          this.forceReinit();
        }
      }, 500);
    },
  };

/**
 * Universal Bootstrap Components Initializer
 */
window.Tro365UniversalBootstrapComponents = {
  /**
   * Initialize Bootstrap tooltips
   */
  initializeTooltips: function () {
    const tooltipTriggerList = document.querySelectorAll(
      '[data-bs-toggle="tooltip"]'
    );
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
      try {
        if (!tooltipTriggerEl.hasAttribute("data-tooltip-initialized")) {
          new bootstrap.Tooltip(tooltipTriggerEl);
          tooltipTriggerEl.setAttribute("data-tooltip-initialized", "true");
        }
      } catch (error) {
        console.warn("Failed to initialize tooltip:", error);
      }
    });
  },

  /**
   * Initialize Bootstrap popovers
   */
  initializePopovers: function () {
    const popoverTriggerList = document.querySelectorAll(
      '[data-bs-toggle="popover"]'
    );
    popoverTriggerList.forEach(function (popoverTriggerEl) {
      try {
        if (!popoverTriggerEl.hasAttribute("data-popover-initialized")) {
          new bootstrap.Popover(popoverTriggerEl);
          popoverTriggerEl.setAttribute("data-popover-initialized", "true");
        }
      } catch (error) {
        console.warn("Failed to initialize popover:", error);
      }
    });
  },

  /**
   * Initialize all Bootstrap components
   */
  initAll: function () {
    try {
      this.initializeTooltips();
      this.initializePopovers();
      console.log("Universal Bootstrap components initialized successfully");
    } catch (error) {
      console.error(
        "Failed to initialize universal Bootstrap components:",
        error
      );
    }
  },
};

// Auto-initialize on DOM ready
document.addEventListener("DOMContentLoaded", function () {
  // Initialize universal dropdown manager
  window.Tro365UniversalDropdownManager.autoInit();

  // Initialize universal Bootstrap components
  window.Tro365UniversalBootstrapComponents.initAll();
});

// Re-initialize on dynamic content changes (for AJAX loaded content)
document.addEventListener("contentChanged", function () {
  window.Tro365UniversalDropdownManager.initializeDropdowns();
  window.Tro365UniversalBootstrapComponents.initAll();
});
