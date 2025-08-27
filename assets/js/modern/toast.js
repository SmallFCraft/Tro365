(function () {
  "use strict";

  const CONTAINER_ID = "gm-toast-container";
  const MAX_TOASTS = 4;

  function ensureContainer() {
    let el = document.getElementById(CONTAINER_ID);
    if (!el) {
      el = document.createElement("div");
      el.id = CONTAINER_ID;
      el.className = "gm-toast-container";
      document.body.appendChild(el);
    }
    return el;
  }

  function iconFor(type) {
    switch (type) {
      case "success":
        return "fas fa-check-circle";
      case "error":
        return "fas fa-exclamation-circle";
      case "warning":
        return "fas fa-exclamation-triangle";
      default:
        return "fas fa-info-circle";
    }
  }

  function clampToasts(container) {
    const toasts = container.querySelectorAll(".gm-toast");
    if (toasts.length > MAX_TOASTS) {
      const overflow = toasts.length - MAX_TOASTS;
      for (let i = 0; i < overflow; i++) {
        toasts[i].remove();
      }
    }
  }

  function createToast({ message, type = "info", duration = 3000 }) {
    const container = ensureContainer();
    clampToasts(container);

    const toast = document.createElement("div");
    toast.className = `gm-toast gm-toast--${type}`;
    toast.setAttribute("role", "alert");
    toast.setAttribute("aria-live", "polite");

    toast.innerHTML = `
      <div class="gm-toast__inner">
        <i class="gm-toast__icon ${iconFor(type)}"></i>
        <div class="gm-toast__message">${message}</div>
        <button class="gm-toast__close" aria-label="Close">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="gm-toast__progress"></div>
    `;

    const closeBtn = toast.querySelector(".gm-toast__close");
    closeBtn.addEventListener("click", () => removeToast(toast));

    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
      toast.classList.add("is-visible");
    });

    // Auto dismiss with progress
    if (duration > 0) {
      const progress = toast.querySelector(".gm-toast__progress");
      progress.style.transition = `width ${duration}ms linear`;
      requestAnimationFrame(() => {
        progress.style.width = "0%";
      });
      toast._timer = setTimeout(() => removeToast(toast), duration);
    }

    return toast;
  }

  function removeToast(toast) {
    if (toast._timer) {
      clearTimeout(toast._timer);
    }
    toast.classList.remove("is-visible");
    setTimeout(() => {
      if (toast.parentElement) {
        toast.remove();
      }
    }, 250);
  }

  window.TroToast = {
    show(opts) {
      if (typeof opts === "string") {
        return createToast({ message: opts });
      }
      return createToast(opts || {});
    },
    success(msg, duration) {
      return createToast({
        message: msg,
        type: "success",
        duration: duration ?? 2500,
      });
    },
    error(msg, duration) {
      return createToast({
        message: msg,
        type: "error",
        duration: duration ?? 4000,
      });
    },
    info(msg, duration) {
      return createToast({
        message: msg,
        type: "info",
        duration: duration ?? 3000,
      });
    },
    warning(msg, duration) {
      return createToast({
        message: msg,
        type: "warning",
        duration: duration ?? 3500,
      });
    },
  };
})();
