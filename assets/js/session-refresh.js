/**
 * Session Auto-Refresh for All Areas (Client, Seller, Admin)
 * Tro365 - Website thuê trọ
 */

// Check if SessionRefresh is already defined to prevent redeclaration
if (typeof SessionRefresh === "undefined") {
  class SessionRefresh {
    constructor(options = {}) {
      this.currentUserRole = options.currentUserRole || null;
      this.currentUserStatus = options.currentUserStatus || null;
      this.refreshInterval = options.refreshInterval || 60000; // 1 minute for faster detection
      this.apiEndpoint = "/api/auth/refresh-session";
      this.debug = options.debug || false;

      this.intervalId = null;
      this.init();
    }

    /**
     * Check if user is logged in by checking for session indicators
     */
    isUserLoggedIn() {
      // Check for common session indicators
      return (
        this.currentUserRole !== null ||
        document.body.classList.contains("logged-in") ||
        document.querySelector("[data-user-id]") !== null ||
        window.TRO365_USER_ID !== undefined
      );
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

      // Also refresh on page focus (when user comes back to tab)
      window.addEventListener("focus", () => {
        if (this.isUserLoggedIn()) {
          this.refreshSession();
        }
      });

      // Refresh on page visibility change
      document.addEventListener("visibilitychange", () => {
        if (!document.hidden && this.isUserLoggedIn()) {
          this.refreshSession();
        }
      });

      // Listen for logout events to stop session refresh immediately
      window.addEventListener("beforeunload", () => {
        this.stopRefresh();
      });

      // Listen for custom logout event
      window.addEventListener("userLogout", () => {
        this.stopRefresh();
      });

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

        const data = window.http ? response : response;

        if (data.success && data.user) {
          this.handleSuccessfulRefresh(data.user);
        } else if (data.error === "Not authenticated") {
          this.handleUnauthenticated();
        } else {
          this.handleRefreshError(data);
        }
      } catch (error) {
        this.handleNetworkError(error);
      }
    }

    handleSuccessfulRefresh(user) {
      // Check if role changed
      if (this.currentUserRole !== null && this.currentUserRole !== user.role) {
        this.handleRoleChange(user);
        return;
      }

      // Check if user status changed (active/inactive/banned)
      if (
        this.currentUserStatus !== null &&
        this.currentUserStatus !== user.status
      ) {
        this.handleStatusChange(user);
        return;
      }

      // Update current role and status
      this.currentUserRole = user.role;
      this.currentUserStatus = user.status;

      if (this.debug) {
        console.log(
          "Session refreshed:",
          user.role_name,
          "Status:",
          user.status
        );
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
      // Clear interval if we stored the reference
      // Note: We'd need to store the interval ID to clear it properly
      if (this.debug) {
        console.log("SessionRefresh destroyed");
      }
    }
  }

  // Make SessionRefresh available globally
  window.SessionRefresh = SessionRefresh;
}

// Auto-initialize for all authenticated areas
document.addEventListener("DOMContentLoaded", function () {
  // Check if user is logged in (has currentUserRole set)
  if (window.currentUserRole !== undefined && window.currentUserRole !== null) {
    // Get current user role and status from global variables
    const currentUserRole = window.currentUserRole;
    const currentUserStatus = window.currentUserStatus || 1; // Default to active

    // Initialize session refresh for all areas (only if not already initialized)
    if (!window.sessionRefresh) {
      window.sessionRefresh = new SessionRefresh({
        currentUserRole: currentUserRole,
        currentUserStatus: currentUserStatus,
        debug: window.TRO365_DEBUG || false, // Use global debug flag
      });

      if (window.TRO365_DEBUG) {
        console.log(
          "Session auto-refresh initialized for role:",
          currentUserRole,
          "status:",
          currentUserStatus
        );
      }
    }
  }
});
