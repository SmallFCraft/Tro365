/**
 * Image Fallback Handler
 * Tro365 - Website thuê trọ
 *
 * Handles image loading errors and provides fallback images
 */

(function () {
  "use strict";

  // Default fallback images
  const FALLBACK_IMAGES = {
    post: "/assets/images/default/no-image.png",
    avatar: "/assets/images/default/avatar.svg",
    default: "/assets/images/default/no-image.png",
  };

  /**
   * Handle image error
   * @param {HTMLImageElement} img The image element that failed to load
   * @param {string} type Type of image (post, avatar, default)
   */
  function handleImageError(img, type = "default") {
    // Prevent infinite loop
    if (img.dataset.fallbackApplied) {
      return;
    }

    // Mark as fallback applied
    img.dataset.fallbackApplied = "true";

    // Get appropriate fallback
    const fallbackSrc = FALLBACK_IMAGES[type] || FALLBACK_IMAGES.default;

    // Set fallback image
    img.src = fallbackSrc;

    // Add fallback class for styling
    img.classList.add("image-fallback");

    // Log error for debugging (only in development), skip logs for fallback URLs and non-image endpoints
    const original = img.dataset.originalSrc || img.src;
    const isFallback =
      original.includes("/assets/images/default/no-image.png") ||
      original.includes("/assets/images/default/avatar.svg");
    const looksLikeNonImage =
      /\/post\//.test(original) || /\.(php|html)(\?|$)/i.test(original);
    if (
      (window.location.hostname === "localhost" ||
        window.location.hostname === "127.0.0.1") &&
      !isFallback &&
      !looksLikeNonImage
    ) {
      console.warn("Image failed to load:", original);
    }
  }

  /**
   * Setup automatic image error handling
   */
  function setupImageErrorHandling() {
    // Handle existing images
    document.querySelectorAll("img").forEach(img => {
      // Skip if already has error handler
      if (img.dataset.errorHandlerAdded) {
        return;
      }

      // Store original src
      img.dataset.originalSrc = img.src;

      // Determine image type based on classes or context
      let imageType = "default";
      if (img.classList.contains("post-image") || img.closest(".post-card")) {
        imageType = "post";
      } else if (
        img.classList.contains("profile-avatar") ||
        img.classList.contains("avatar")
      ) {
        imageType = "avatar";
      }

      // Add error handler
      img.addEventListener(
        "error",
        function () {
          handleImageError(this, imageType);
        },
        { passive: true }
      );

      // Mark as handled
      img.dataset.errorHandlerAdded = "true";

      // Check if image is already broken
      if (img.complete && img.naturalWidth === 0) {
        handleImageError(img, imageType);
      }
    });
  }

  /**
   * Setup mutation observer for dynamically added images
   */
  function setupMutationObserver() {
    const observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === Node.ELEMENT_NODE) {
            // Check if the node itself is an image
            if (node.tagName === "IMG") {
              setupImageErrorHandling();
            }
            // Check for images within the added node
            else if (node.querySelectorAll) {
              const images = node.querySelectorAll("img");
              if (images.length > 0) {
                setupImageErrorHandling();
              }
            }
          }
        });
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  }

  /**
   * Preload fallback images
   */
  function preloadFallbackImages() {
    Object.values(FALLBACK_IMAGES).forEach(src => {
      const img = new Image();
      img.src = src;
    });
  }

  /**
   * Add CSS for fallback images
   */
  function addFallbackStyles() {
    const fallbackStyle = document.createElement("style");
    fallbackStyle.textContent = `
            .image-fallback {
                opacity: 0.8;
                filter: grayscale(20%);
            }
            
            .image-fallback:hover {
                opacity: 1;
                filter: grayscale(0%);
            }
            
            /* Specific styles for different image types */
            .post-image.image-fallback {
                background-color: #f8f9fa;
                border: 2px dashed #dee2e6;
            }
            
            .avatar.image-fallback,
            .profile-avatar.image-fallback {
                background-color: #e9ecef;
                border: 2px solid #dee2e6;
            }
        `;
    document.head.appendChild(fallbackStyle);
  }

  /**
   * Initialize image fallback system
   */
  function init() {
    // Add fallback styles
    addFallbackStyles();

    // Preload fallback images
    preloadFallbackImages();

    // Setup error handling for existing images
    setupImageErrorHandling();

    // Setup mutation observer for dynamic content
    setupMutationObserver();

    // Re-check images periodically (for lazy loading)
    setInterval(setupImageErrorHandling, 2000);
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  // Expose global function for manual error handling
  window.handleImageError = handleImageError;

  // Expose function to manually setup error handling
  window.setupImageErrorHandling = setupImageErrorHandling;
})();
