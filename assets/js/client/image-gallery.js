/**
 * Modern Image Gallery with Lightbox & Performance Optimization
 * Tro365 - Website thuê trọ
 */

class ImageGallery {
  constructor(container) {
    this.container = container;
    this.images = [];
    this.currentIndex = 0;
    this.lightbox = null;
    this.maxThumbnails = 8; // Reduced for better mobile performance
    this.showingAllThumbnails = false;
    this.isLoading = false;
    this.loadedImages = new Set();
    this.intersectionObserver = null;

    this.init();
  }

  init() {
    this.collectImages();
    this.createLightbox();
    this.bindEvents();
    this.updateThumbnailGrid();
    this.setupLazyLoading();
  }

  collectImages() {
    // Collect all gallery images but exclude the main image to avoid duplication
    const imageElements = this.container.querySelectorAll(
      "[data-gallery-image]:not(.main-image)"
    );
    this.images = Array.from(imageElements).map((img, index) => ({
      src: img.dataset.galleryImage,
      alt: img.alt || `Ảnh ${index + 1}`,
      element: img,
      loaded: false,
      thumbnail: null,
    }));

    // If no hidden gallery images found, use the main image as single image
    if (this.images.length === 0) {
      const mainImage = this.container.querySelector(
        ".main-image[data-gallery-image]"
      );
      if (mainImage) {
        this.images = [
          {
            src: mainImage.dataset.galleryImage,
            alt: mainImage.alt || "Ảnh chính",
            element: mainImage,
            loaded: false,
            thumbnail: null,
          },
        ];
      }
    }
  }

  setupLazyLoading() {
    if ("IntersectionObserver" in window) {
      this.intersectionObserver = new IntersectionObserver(
        entries => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              this.loadImage(entry.target);
              this.intersectionObserver.unobserve(entry.target);
            }
          });
        },
        {
          rootMargin: "50px",
        }
      );
    }
  }

  loadImage(element) {
    if (this.loadedImages.has(element.src)) return;

    const img = new Image();
    img.onload = () => {
      element.src = img.src;
      element.classList.add("loaded");
      this.loadedImages.add(element.src);
    };
    img.onerror = () => {
      element.src = "/assets/images/default/no-image.png";
      element.classList.add("error");
    };
    img.src = element.dataset.src || element.src;
  }

  createLightbox() {
    this.lightbox = document.createElement("div");
    this.lightbox.className = "image-lightbox";
    this.lightbox.innerHTML = `
            <div class="lightbox-content">
                <div class="lightbox-image-container">
                    <img class="lightbox-image" src="" alt="">
                    <div class="lightbox-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
                <button class="lightbox-close" aria-label="Đóng">
                    <i class="fas fa-times"></i>
                </button>
                <button class="lightbox-nav prev" aria-label="Ảnh trước">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="lightbox-nav next" aria-label="Ảnh sau">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <div class="lightbox-counter">
                    <span class="current">1</span> / <span class="total">${this.images.length}</span>
                </div>
                <div class="lightbox-zoom-controls">
                    <button class="zoom-in" aria-label="Phóng to">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button class="zoom-out" aria-label="Thu nhỏ">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button class="zoom-reset" aria-label="Kích thước gốc">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </button>
                </div>
            </div>
        `;

    document.body.appendChild(this.lightbox);
    this.setupZoomControls();
    this.currentZoom = 1;
    this.maxZoom = 3;
    this.minZoom = 0.5;
  }

  setupZoomControls() {
    if (!this.lightbox) return;

    const zoomIn = this.lightbox.querySelector(".zoom-in");
    const zoomOut = this.lightbox.querySelector(".zoom-out");
    const zoomReset = this.lightbox.querySelector(".zoom-reset");

    if (zoomIn) {
      zoomIn.addEventListener("click", () => this.zoomIn());
    }
    if (zoomOut) {
      zoomOut.addEventListener("click", () => this.zoomOut());
    }
    if (zoomReset) {
      zoomReset.addEventListener("click", () => this.resetZoom());
    }
  }

  zoomIn() {
    if (this.currentZoom < this.maxZoom) {
      this.currentZoom += 0.25;
      this.applyZoom();
      this.updateZoomButtons();
    }
  }

  zoomOut() {
    if (this.currentZoom > this.minZoom) {
      this.currentZoom -= 0.25;
      this.applyZoom();
      this.updateZoomButtons();
    }
  }

  resetZoom() {
    this.currentZoom = 1;
    this.applyZoom();
    this.updateZoomButtons();
  }

  applyZoom() {
    const lightboxImage = this.lightbox.querySelector(".lightbox-image");
    if (lightboxImage) {
      lightboxImage.style.transform = `scale(${this.currentZoom})`;
      lightboxImage.style.transition = "transform 0.3s ease";
    }
  }

  updateZoomButtons() {
    if (!this.lightbox) return;

    const zoomInBtn = this.lightbox.querySelector(".zoom-in");
    const zoomOutBtn = this.lightbox.querySelector(".zoom-out");

    if (zoomInBtn) {
      zoomInBtn.disabled = this.currentZoom >= this.maxZoom;
    }
    if (zoomOutBtn) {
      zoomOutBtn.disabled = this.currentZoom <= this.minZoom;
    }
  }

  bindEvents() {
    // Main image click to open lightbox
    const mainImage = this.container.querySelector(".main-image");
    if (mainImage) {
      mainImage.addEventListener("click", () => this.openLightbox(0));
    }

    // Fullscreen button
    const fullscreenBtn = this.container.querySelector(".fullscreen-btn");
    if (fullscreenBtn) {
      fullscreenBtn.addEventListener("click", () =>
        this.openLightbox(this.currentIndex)
      );
    }

    // Thumbnail clicks
    this.container.addEventListener("click", e => {
      if (e.target.closest(".thumbnail-item")) {
        const index = parseInt(
          e.target.closest(".thumbnail-item").dataset.index
        );
        this.changeMainImage(index);
      }

      if (e.target.closest(".show-more-thumbnails")) {
        this.toggleAllThumbnails();
      }
    });

    // Lightbox events
    if (this.lightbox) {
      const closeBtn = this.lightbox.querySelector(".lightbox-close");
      const prevBtn = this.lightbox.querySelector(".lightbox-nav.prev");
      const nextBtn = this.lightbox.querySelector(".lightbox-nav.next");

      closeBtn.addEventListener("click", () => this.closeLightbox());
      prevBtn.addEventListener("click", () => this.previousImage());
      nextBtn.addEventListener("click", () => this.nextImage());

      // Close on backdrop click
      this.lightbox.addEventListener("click", e => {
        if (e.target === this.lightbox) {
          this.closeLightbox();
        }
      });

      // Keyboard navigation
      document.addEventListener("keydown", e => {
        if (!this.lightbox.classList.contains("active")) return;

        switch (e.key) {
          case "Escape":
            this.closeLightbox();
            break;
          case "ArrowLeft":
            this.previousImage();
            break;
          case "ArrowRight":
            this.nextImage();
            break;
          case "+":
          case "=":
            this.zoomIn();
            break;
          case "-":
            this.zoomOut();
            break;
          case "0":
            this.resetZoom();
            break;
        }
      });

      // Mouse wheel zoom (passive listener to avoid scroll-blocking; we don't call preventDefault)
      this.lightbox.addEventListener(
        "wheel",
        e => {
          if (!this.lightbox.classList.contains("active")) return;
          if (e.deltaY < 0) {
            this.zoomIn();
          } else {
            this.zoomOut();
          }
        },
        { passive: true }
      );

      // Double-click to zoom
      this.lightbox.addEventListener("dblclick", e => {
        if (!this.lightbox.classList.contains("active")) return;

        if (this.currentZoom === 1) {
          this.currentZoom = 2;
        } else {
          this.currentZoom = 1;
        }
        this.applyZoom();
      });
    }

    // Touch/swipe support for mobile
    this.addTouchSupport();

    // Handle window resize for responsive updates
    this.handleResize();
  }

  changeMainImage(index) {
    if (index < 0 || index >= this.images.length) return;

    this.currentIndex = index;
    const mainImage = this.container.querySelector(".main-image");

    if (mainImage) {
      mainImage.src = this.images[index].src;
      mainImage.alt = this.images[index].alt;
    }

    // Update active thumbnail
    this.container.querySelectorAll(".thumbnail-item").forEach((thumb, i) => {
      thumb.classList.toggle("active", i === index);
    });

    // Update thumbnail counter in UI (outside lightbox)
    const visibleSpan = this.container.querySelector("#visibleThumbnails");
    const totalSpan = this.container.querySelector("#totalThumbnails");
    const container = this.container.querySelector(".thumbnail-container");
    if (visibleSpan && totalSpan && container) {
      const thumbs = Array.from(container.querySelectorAll(".thumbnail-item"));
      // Show current image index / total images
      visibleSpan.textContent = this.currentIndex + 1;
      totalSpan.textContent = thumbs.length;
    }
  }

  updateThumbnailGrid() {
    const thumbnailContainer = this.container.querySelector(
      ".thumbnail-container"
    );
    if (!thumbnailContainer || this.images.length <= 1) return;

    // For carousel, we load thumbnails progressively
    const initialLoadCount = Math.min(8, this.images.length); // Load first 8 thumbnails

    // Use DocumentFragment for better performance
    const fragment = document.createDocumentFragment();

    // Add all thumbnails with progressive loading
    for (let i = 0; i < this.images.length; i++) {
      const image = this.images[i];
      const thumbnailDiv = document.createElement("div");
      thumbnailDiv.className = `thumbnail-item ${
        i === this.currentIndex ? "active" : ""
      }`;
      thumbnailDiv.dataset.index = i;

      const img = document.createElement("img");
      img.alt = image.alt;
      img.className = "thumbnail-img";

      // Load first 8 thumbnails immediately, rest are lazy loaded
      if (i < initialLoadCount) {
        img.src = image.src;
        img.classList.add("loaded");
      } else {
        img.classList.add("lazy");
        img.dataset.src = image.src;
        // Add loading placeholder
        img.src =
          "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA4MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjgwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0zNSAyNUg0NVYzNUgzNVYyNVoiIGZpbGw9IiM5Q0E0QUYiLz4KPHBhdGggZD0iTTMwIDMwTDM1IDI1TDQwIDMwTDQ1IDI1TDUwIDMwVjQwSDMwVjMwWiIgZmlsbD0iIzlDQTRBRiIvPgo8L3N2Zz4K";
      }

      thumbnailDiv.appendChild(img);

      // Add click event
      thumbnailDiv.addEventListener("click", () => {
        this.currentIndex = i;
        this.changeMainImage(i);
        this.scrollToActiveThumbnail();
        // Ensure counter updates on click
        const visibleSpan = this.container.querySelector("#visibleThumbnails");
        const totalSpan = this.container.querySelector("#totalThumbnails");
        if (visibleSpan && totalSpan) {
          // trigger recalculation in changeMainImage already
        }
      });

      fragment.appendChild(thumbnailDiv);

      // Setup lazy loading for thumbnails beyond initial load
      if (i >= initialLoadCount && this.intersectionObserver) {
        this.intersectionObserver.observe(img);
      }
    }

    // Clear and append all at once for better performance
    thumbnailContainer.innerHTML = "";
    thumbnailContainer.appendChild(fragment);

    // Update carousel navigation
    this.updateCarouselNavigation();
  }

  scrollToActiveThumbnail() {
    const thumbnailContainer = this.container.querySelector(
      ".thumbnail-container"
    );
    const activeThumbnail = thumbnailContainer?.querySelector(
      ".thumbnail-item.active"
    );

    if (activeThumbnail && thumbnailContainer) {
      const containerWidth = thumbnailContainer.clientWidth;
      const thumbnailLeft = activeThumbnail.offsetLeft;
      const thumbnailWidth = activeThumbnail.offsetWidth;

      // Calculate scroll position to center the active thumbnail
      const scrollLeft =
        thumbnailLeft - containerWidth / 2 + thumbnailWidth / 2;

      thumbnailContainer.scrollTo({
        left: scrollLeft,
        behavior: "smooth",
      });
    }
  }

  updateCarouselNavigation() {
    const container = this.container.querySelector(".thumbnail-container");
    const prevBtn = this.container.querySelector(".carousel-nav.prev");
    const nextBtn = this.container.querySelector(".carousel-nav.next");

    if (container && prevBtn && nextBtn) {
      prevBtn.disabled = container.scrollLeft <= 0;
      nextBtn.disabled =
        container.scrollLeft >= container.scrollWidth - container.clientWidth;
    }
  }

  toggleAllThumbnails() {
    this.showingAllThumbnails = !this.showingAllThumbnails;
    this.updateThumbnailGrid();
  }

  openLightbox(index = 0) {
    if (!this.lightbox || this.images.length === 0) return;

    this.currentIndex = index;
    this.resetZoom(); // Reset zoom when opening
    this.updateLightboxImage();
    this.updateZoomButtons();
    this.lightbox.classList.add("active");
    document.body.style.overflow = "hidden";

    // Add escape key listener
    this.addEscapeKeyListener();
  }

  closeLightbox() {
    if (!this.lightbox) return;

    this.lightbox.classList.remove("active");
    document.body.style.overflow = "";
    this.resetZoom(); // Reset zoom when closing
  }

  addEscapeKeyListener() {
    // Remove existing listener to avoid duplicates
    document.removeEventListener("keydown", this.escapeKeyHandler);

    // Add new listener
    this.escapeKeyHandler = e => {
      if (e.key === "Escape" && this.lightbox.classList.contains("active")) {
        this.closeLightbox();
      }
    };

    document.addEventListener("keydown", this.escapeKeyHandler);
  }

  previousImage() {
    if (this.currentIndex > 0) {
      this.currentIndex--;
      this.updateLightboxImage();
    }
  }

  nextImage() {
    if (this.currentIndex < this.images.length - 1) {
      this.currentIndex++;
      this.updateLightboxImage();
    }
  }

  updateLightboxImage() {
    if (!this.lightbox) return;

    const lightboxImage = this.lightbox.querySelector(".lightbox-image");
    const lightboxLoading = this.lightbox.querySelector(".lightbox-loading");
    const counter = this.lightbox.querySelector(".lightbox-counter");
    const prevBtn = this.lightbox.querySelector(".lightbox-nav.prev");
    const nextBtn = this.lightbox.querySelector(".lightbox-nav.next");

    // Reset zoom when changing images
    this.resetZoom();

    // Show loading state
    if (lightboxLoading) {
      lightboxLoading.style.display = "flex";
    }

    if (lightboxImage) {
      // Hide image while loading
      lightboxImage.style.opacity = "0";

      // Create new image to preload
      const newImage = new Image();
      newImage.onload = () => {
        lightboxImage.src = newImage.src;
        lightboxImage.alt = this.images[this.currentIndex].alt;
        lightboxImage.style.opacity = "1";
        if (lightboxLoading) {
          lightboxLoading.style.display = "none";
        }
      };
      newImage.onerror = () => {
        if (lightboxLoading) {
          lightboxLoading.style.display = "none";
        }
        lightboxImage.style.opacity = "1";
      };
      newImage.src = this.images[this.currentIndex].src;
    }

    if (counter) {
      counter.querySelector(".current").textContent = this.currentIndex + 1;
      counter.querySelector(".total").textContent = this.images.length;
    }

    // Update navigation buttons
    if (prevBtn) prevBtn.disabled = this.currentIndex === 0;
    if (nextBtn)
      nextBtn.disabled = this.currentIndex === this.images.length - 1;

    // Update main gallery if lightbox is open
    this.changeMainImage(this.currentIndex);
  }

  handleResize() {
    let resizeTimeout;
    window.addEventListener("resize", () => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        this.updateThumbnailGrid();
      }, 250);
    });
  }

  addTouchSupport() {
    let startX = 0;
    let startY = 0;

    if (this.lightbox) {
      this.lightbox.addEventListener(
        "touchstart",
        e => {
          startX = e.touches[0].clientX;
          startY = e.touches[0].clientY;
        },
        { passive: true }
      );

      this.lightbox.addEventListener(
        "touchend",
        e => {
          if (!this.lightbox.classList.contains("active")) return;

          const endX = e.changedTouches[0].clientX;
          const endY = e.changedTouches[0].clientY;
          const diffX = startX - endX;
          const diffY = startY - endY;

          // Only handle horizontal swipes
          if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
            if (diffX > 0) {
              this.nextImage();
            } else {
              this.previousImage();
            }
          }
        },
        { passive: true }
      );
    }
  }

  destroy() {
    if (this.lightbox) {
      this.lightbox.remove();
    }
  }
}

// Auto-initialize galleries
document.addEventListener("DOMContentLoaded", () => {
  const galleries = document.querySelectorAll(".post-image-gallery");
  galleries.forEach(gallery => {
    new ImageGallery(gallery);
  });
});

// Export for manual initialization
window.ImageGallery = ImageGallery;
