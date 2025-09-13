/**
 * Session Auto-Refresh for All Areas (Client, Seller, Admin)
 * Tro365 - Website thuê trọ
 */

// Check if SessionRefresh is already defined to prevent redeclaration
if (typeof SessionRefresh === "undefined") {
  class SessionRefresh {
    static instance = null; // Singleton pattern
    constructor(options = {}) {
      // Singleton pattern - prevent multiple instances
      if (SessionRefresh.instance) {
        console.warn(
          "SessionRefresh already exists, returning existing instance"
        );
        return SessionRefresh.instance;
      }

      this.currentUserRole = options.currentUserRole || null;
      this.currentUserStatus = options.currentUserStatus || null;
      this.refreshInterval = options.refreshInterval || 300000; // 5 minutes to reduce spam
      this.apiEndpoint = "/api/auth/refresh-session";
      this.debug = options.debug || false;
      this.lastLogTime = 0; // Throttle debug logs

      this.intervalId = null;
      SessionRefresh.instance = this;
      this.init();
    }

    /**
     * Check if user is logged in by checking for session indicators
     */
    isUserLoggedIn() {
      // Check for common session indicators
      const hasUserRole =
        this.currentUserRole !== null && this.currentUserRole !== undefined;
      const hasGlobalUserRole =
        window.currentUserRole !== null && window.currentUserRole !== undefined;
      const hasBodyClass = document.body.classList.contains("logged-in");
      const hasUserIdElement =
        document.querySelector("[data-user-id]") !== null;
      const hasGlobalUserId = window.TRO365_USER_ID !== undefined;
      const hasConfigLoggedIn =
        window.Tro365Config && window.Tro365Config.isLoggedIn === true;

      // More strict checking - require at least 2 indicators to be true
      const indicators = [
        hasUserRole,
        hasGlobalUserRole,
        hasBodyClass,
        hasUserIdElement,
        hasGlobalUserId,
        hasConfigLoggedIn,
      ];
      const trueCount = indicators.filter(Boolean).length;

      // Throttle debug logging to reduce spam
      if (this.debug && Date.now() - this.lastLogTime > 30000) {
        // Only log every 30 seconds
        console.log("SessionRefresh: Login indicators:", {
          hasUserRole,
          hasGlobalUserRole,
          hasBodyClass,
          hasUserIdElement,
          hasGlobalUserId,
          hasConfigLoggedIn,
          trueCount,
          isLoggedIn: trueCount >= 2,
        });
        this.lastLogTime = Date.now();
      }

      return trueCount >= 2;
    }

    /**
     * Stop session refresh
     */
    stopRefresh() {
      if (this.intervalId) {
        clearInterval(this.intervalId);
        this.intervalId = null;
        if (this.debug) {
          console.log("SessionRefresh: Stopped session refresh");
        }
      }
    }

    init() {
      // Only initialize if user is logged in
      if (!this.isUserLoggedIn()) {
        if (this.debug) {
          console.log(
            "SessionRefresh: User not logged in, skipping initialization"
          );
        }
        return;
      }

      // Auto-refresh every interval
      this.intervalId = setInterval(() => {
        if (this.isUserLoggedIn()) {
          this.refreshSession();
        } else {
          this.stopRefresh();
        }
      }, this.refreshInterval);

      // Store event handlers for later removal
      this.focusHandler = () => {
        if (this.isUserLoggedIn()) {
          this.refreshSession();
        }
      };

      this.visibilityHandler = () => {
        if (!document.hidden && this.isUserLoggedIn()) {
          this.refreshSession();
        }
      };

      this.beforeUnloadHandler = () => {
        this.stopRefresh();
      };

      this.userLogoutHandler = () => {
        this.stopRefresh();
      };

      // Also refresh on page focus (when user comes back to tab)
      window.addEventListener("focus", this.focusHandler);

      // Refresh on page visibility change
      document.addEventListener("visibilitychange", this.visibilityHandler);

      // Listen for logout events to stop session refresh immediately
      window.addEventListener("beforeunload", this.beforeUnloadHandler);

      // Listen for custom logout event
      window.addEventListener("userLogout", this.userLogoutHandler);

      if (this.debug) {
        console.log(
          "SessionRefresh initialized with role:",
          this.currentUserRole
        );
      }
    }

    async refreshSession() {
      try {
        // Use modern HTTP client if available, otherwise fallback to fetch
        const response = window.http
          ? await window.http.post(
              this.apiEndpoint,
              {},
              {
                headers: { "X-Requested-With": "XMLHttpRequest" },
              }
            )
          : await fetch(this.apiEndpoint, {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
              },
              credentials: "same-origin",
            }).then(res => res.json());

        // Normalize standardized API shape: { success, message, data: { user, ... } }
        const payload = response;
        const user =
          payload && payload.success
            ? (payload.data && payload.data.user) || payload.user || null
            : null;

        if (user) {
          this.handleSuccessfulRefresh(user);
        } else if (
          payload &&
          (payload.error === "Not authenticated" ||
            payload.message === "Not authenticated")
        ) {
          this.handleUnauthenticated();
        } else if (payload && payload.success) {
          // Successful response but missing user object — treat as benign
          if (this.debug) {
            console.info("Session refresh ok (no user payload)", payload);
          }
        } else {
          this.handleRefreshError(payload || { error: "unknown_response" });
        }
      } catch (error) {
        this.handleNetworkError(error);
      }
    }

    handleSuccessfulRefresh(user) {
      // Normalize numeric fields if present
      const newRole =
        typeof user.role !== "undefined" && user.role !== null
          ? Number(user.role)
          : null;
      const newStatus =
        typeof user.status !== "undefined" && user.status !== null
          ? Number(user.status)
          : null;
      const oldRole =
        this.currentUserRole !== null && this.currentUserRole !== undefined
          ? Number(this.currentUserRole)
          : null;
      const oldStatus =
        this.currentUserStatus !== null && this.currentUserStatus !== undefined
          ? Number(this.currentUserStatus)
          : null;

      // Check if role changed (only when both values are available)
      if (oldRole !== null && newRole !== null && oldRole !== newRole) {
        this.handleRoleChange({ ...user, role: newRole });
        return;
      }

      // Check if user status changed (only when both values are available)
      if (oldStatus !== null && newStatus !== null && oldStatus !== newStatus) {
        this.handleStatusChange({ ...user, status: newStatus });
        return;
      }

      // Update current role and status (only set when present)
      if (newRole !== null) this.currentUserRole = newRole;
      if (newStatus !== null) this.currentUserStatus = newStatus;

      // Throttle debug logging and handle undefined fields properly
      if (this.debug && Date.now() - this.lastLogTime > 30000) {
        console.log(
          "Session refreshed:",
          `Role: ${user.role || "N/A"}`,
          `Status: ${user.status || "N/A"}`,
          `User: ${user.username || user.id || "N/A"}`
        );
        this.lastLogTime = Date.now();
      }

      // Trigger custom event for other components
      window.dispatchEvent(
        new CustomEvent("sessionRefreshed", {
          detail: { user },
        })
      );
    }

    handleRoleChange(user) {
      // Role changed - automatically refresh page to apply new permissions
      if (this.debug) {
        console.log("Role changed from", this.currentUserRole, "to", user.role);
      }

      // Trigger custom event before refresh
      window.dispatchEvent(
        new CustomEvent("roleChanged", {
          detail: {
            oldRole: this.currentUserRole,
            newRole: user.role,
            user,
          },
        })
      );

      // Auto-refresh page to apply new role permissions
      setTimeout(() => {
        window.location.reload();
      }, 1000); // Small delay to allow event handlers to complete
    }

    handleStatusChange(user) {
      // User status changed - check if user is banned or deactivated
      if (this.debug) {
        console.log(
          "Status changed from",
          this.currentUserStatus,
          "to",
          user.status
        );
      }

      // Trigger custom event before action
      window.dispatchEvent(
        new CustomEvent("statusChanged", {
          detail: {
            oldStatus: this.currentUserStatus,
            newStatus: user.status,
            user,
          },
        })
      );

      // Handle different status changes
      if (user.status === 0 || user.status === 2) {
        // User deactivated (0) or banned (2) - redirect to login
        setTimeout(() => {
          window.location.href =
            "/login?reason=account_" +
            (user.status === 2 ? "banned" : "deactivated");
        }, 1000);
      } else {
        // Status changed but still active - refresh page
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      }
    }

    getRedirectUrlForRole(roleId) {
      // Define role constants (should match backend)
      const ROLE_USER = 1;
      const ROLE_SELLER = 2;
      const ROLE_SUPPORTER = 3;
      const ROLE_MODERATOR = 4;
      const ROLE_ADMIN = 5;

      switch (roleId) {
        case ROLE_ADMIN:
        case ROLE_MODERATOR:
          return "/admin";
        case ROLE_SUPPORTER:
          return "/supporter";
        case ROLE_SELLER:
          return "/seller";
        case ROLE_USER:
        default:
          return "/profile";
      }
    }

    handleUnauthenticated() {
      // Stop session refresh to prevent further 401 errors
      this.stopRefresh();

      // User logged out - automatically redirect to login
      if (this.debug) {
        console.log("User unauthenticated - redirecting to login");
      }

      // Trigger custom event
      window.dispatchEvent(
        new CustomEvent("userUnauthenticated", {
          detail: { reason: "session_expired" },
        })
      );

      // Auto-redirect to login (only if not already on login page)
      if (!window.location.pathname.includes("/login")) {
        setTimeout(() => {
          window.location.href = "/login?reason=session_expired";
        }, 1000);
      }
    }

    handleRefreshError(data) {
      if (this.debug) {
        console.error("Session refresh failed:", data);
      }

      // Trigger custom event for error handling
      window.dispatchEvent(
        new CustomEvent("sessionRefreshError", {
          detail: { error: data },
        })
      );
    }

    handleNetworkError(error) {
      if (this.debug) {
        console.error("Session refresh network error:", error);
      }

      // Trigger custom event for network error handling
      window.dispatchEvent(
        new CustomEvent("sessionNetworkError", {
          detail: { error },
        })
      );
    }

    // Manual refresh method
    forceRefresh() {
      this.refreshSession();
    }

    // Update current role (for external updates)
    updateCurrentRole(role) {
      this.currentUserRole = role;
    }

    // Destroy instance
    destroy() {
      // Stop refresh first
      this.stopRefresh();

      // Clear all properties
      this.currentUserRole = null;
      this.currentUserStatus = null;
      this.intervalId = null;

      // Remove event listeners
      window.removeEventListener("focus", this.focusHandler);
      document.removeEventListener("visibilitychange", this.visibilityHandler);
      window.removeEventListener("beforeunload", this.beforeUnloadHandler);
      window.removeEventListener("userLogout", this.userLogoutHandler);

      // Clear singleton instance
      SessionRefresh.instance = null;

      if (this.debug) {
        console.log("SessionRefresh destroyed completely");
      }
    }
  }

  // Make SessionRefresh available globally
  window.SessionRefresh = SessionRefresh;
}

// Auto-initialize for all authenticated areas
document.addEventListener("DOMContentLoaded", function () {
  // More comprehensive check for user authentication
  const hasUserRole =
    window.currentUserRole !== undefined && window.currentUserRole !== null;
  const hasConfig =
    window.Tro365Config && window.Tro365Config.isLoggedIn === true;
  const hasBodyClass = document.body.classList.contains("logged-in");

  // Only initialize if we have strong indicators of being logged in
  if (
    (hasUserRole || hasConfig) &&
    !window.location.pathname.includes("/logout")
  ) {
    // Get current user role and status from global variables
    const currentUserRole = window.currentUserRole;
    const currentUserStatus =
      typeof window.currentUserStatus !== "undefined"
        ? window.currentUserStatus
        : null; // Default to null to avoid false change detection

    // Initialize session refresh for all areas (only if not already initialized)
    if (!window.sessionRefresh) {
      window.sessionRefresh = new SessionRefresh({
        currentUserRole: currentUserRole,
        currentUserStatus: currentUserStatus,
        debug: false, // Disable debug to reduce spam - enable only when needed
      });

      // Reduce initialization logging spam
      if (window.TRO365_DEBUG) {
        console.info(
          "Session auto-refresh initialized:",
          `Role: ${currentUserRole || "N/A"}`,
          `Status: ${currentUserStatus || "N/A"}`
        );
      }
    }
  } else if (window.TRO365_DEBUG) {
    console.log(
      "Session auto-refresh skipped - user not authenticated",
      "indicators:",
      {
        hasUserRole,
        hasConfig,
        hasBodyClass,
        pathname: window.location.pathname,
      }
    );
  }
});
