/**
 * Modern Navigation JavaScript
 * Tro365 - Website thuê trọ
 * Mobile-First Responsive Navigation with Modern Features
 */

class ModernNavigation {
  constructor() {
    // Cache viewport size to avoid repeated forced reflow
    this.isMobile = false;
    this.viewportWidth = 0;
    this.resizeTimeout = null;
    this.updateViewportCache();

    this.init();
    this.bindEvents();
    this.initTheme();
    this.initSearch();
    this.initNotifications();
    this.initLocationAPI();
    this.setActiveNavigation();
  }

  init() {
    // Body padding is now handled by CSS to prevent CLS
    // Removed: document.body.style.paddingTop = "80px";

    // Initialize mobile bottom nav visibility
    this.updateMobileNavVisibility();

    // Initialize scroll behavior
    this.initScrollBehavior();

    if (window.TRO365_DEBUG) {
      console.log("Modern Navigation initialized");
    }
  }

  updateViewportCache() {
    // Use ResizeObserver API for efficient viewport tracking to avoid forced reflow
    if (!this.resizeObserver) {
      this.resizeObserver = new ResizeObserver(() => {
        // Use requestIdleCallback to defer non-critical viewport updates
        if ("requestIdleCallback" in window) {
          requestIdleCallback(() => {
            this.viewportWidth = window.innerWidth;
            this.isMobile = this.viewportWidth <= 767;
          });
        } else {
          // Fallback for browsers without requestIdleCallback
          setTimeout(() => {
            this.viewportWidth = window.innerWidth;
            this.isMobile = this.viewportWidth <= 767;
          }, 0);
        }
      });
      this.resizeObserver.observe(document.documentElement);
    }

    // Initial viewport detection - only run once
    if (this.viewportWidth === 0) {
      this.viewportWidth = window.innerWidth;
      this.isMobile = this.viewportWidth <= 767;
    }
  }

  updateMobileNavVisibility() {
    const mobileNav = document.getElementById("mobileBottomNav");
    if (mobileNav) {
      // Use cached viewport size to avoid forced reflow
      // Only update cache if needed (throttled)
      if (this.viewportWidth === 0) {
        this.updateViewportCache();
      }

      // Batch DOM updates to minimize reflow
      requestAnimationFrame(() => {
        if (this.isMobile) {
          mobileNav.classList.add("mobile-nav-visible");
          document.body.classList.add("mobile-nav-padding");
        } else {
          mobileNav.classList.remove("mobile-nav-visible");
          document.body.classList.remove("mobile-nav-padding");
        }
      });
    }
  }

  initScrollBehavior() {
    let lastScrollTop = 0;
    const navbar = document.getElementById("mainNavbar");

    if (!navbar) return;

    window.addEventListener("scroll", () => {
      const scrollTop =
        window.pageYOffset || document.documentElement.scrollTop;

      if (scrollTop > lastScrollTop && scrollTop > 100) {
        // Scrolling down
        navbar.style.transform = "translateY(-100%)";
      } else {
        // Scrolling up
        navbar.style.transform = "translateY(0)";
      }

      lastScrollTop = scrollTop;
    });
  }

  handleResize() {
    // Use requestAnimationFrame to prevent layout thrashing
    if (this.resizeRAF) {
      cancelAnimationFrame(this.resizeRAF);
    }

    this.resizeRAF = requestAnimationFrame(() => {
      // Batch DOM reads and writes to prevent forced reflow
      this.updateMobileNavVisibility();
    });
  }

  handleScroll() {
    // Handle scroll effects if needed
  }

  handleKeyboardShortcuts(e) {
    // Ctrl/Cmd + K to open search
    if ((e.ctrlKey || e.metaKey) && e.key === "k") {
      e.preventDefault();
      this.openSearch();
    }

    // Escape to close overlays
    if (e.key === "Escape") {
      this.closeSearch();
      this.closeUserMenu();
      this.closeNotifications();
    }
  }

  bindEvents() {
    // Theme toggle
    const themeToggle = document.getElementById("themeToggle");
    if (themeToggle) {
      themeToggle.addEventListener("click", () => this.toggleTheme());
    }

    // Search toggle
    const searchToggle = document.getElementById("searchToggle");
    const searchClose = document.getElementById("searchClose");
    const searchOverlay = document.getElementById("searchOverlay");

    if (searchToggle) {
      searchToggle.addEventListener("click", () => this.openSearch());
    }
    if (searchClose) {
      searchClose.addEventListener("click", () => this.closeSearch());
    }
    if (searchOverlay) {
      searchOverlay.addEventListener("click", e => {
        if (e.target === searchOverlay) this.closeSearch();
      });
    }

    // Mobile quick search
    const mobileQuickSearch = document.getElementById("mobileQuickSearch");
    if (mobileQuickSearch) {
      mobileQuickSearch.addEventListener("click", () => this.openSearch());
    }

    // User menu toggle
    const userMenuToggle = document.getElementById("userMenuToggle");
    const userMenuWrapper = document.querySelector(".user-menu-wrapper");

    if (userMenuToggle && userMenuWrapper) {
      userMenuToggle.addEventListener("click", e => {
        e.preventDefault();
        this.toggleUserMenu();
      });
    }

    // Notification toggle
    const notificationToggle = document.getElementById("notificationToggle");
    const notificationWrapper = document.querySelector(".notification-wrapper");

    if (notificationToggle && notificationWrapper) {
      notificationToggle.addEventListener("click", e => {
        e.preventDefault();
        this.toggleNotifications();
      });
    }

    // Close dropdowns when clicking outside
    document.addEventListener("click", e => {
      if (!e.target.closest(".user-menu-wrapper")) {
        this.closeUserMenu();
      }
      if (!e.target.closest(".notification-wrapper")) {
        this.closeNotifications();
      }
    });

    // Advanced search form
    const advancedSearchForm = document.getElementById("advancedSearchForm");
    if (advancedSearchForm) {
      advancedSearchForm.addEventListener("submit", e =>
        this.handleAdvancedSearch(e)
      );
    }

    // Reset search
    const resetSearch = document.getElementById("resetSearch");
    if (resetSearch) {
      resetSearch.addEventListener("click", () => this.resetSearchForm());
    }

    // Voice search - Support both standard and webkit
    const voiceSearch = document.getElementById("voiceSearch");
    if (voiceSearch && this.isSpeechRecognitionSupported()) {
      voiceSearch.addEventListener("click", () => this.startVoiceSearch());
    } else if (voiceSearch) {
      voiceSearch.style.display = "none";
    }

    // Location detection
    const detectLocation = document.getElementById("detectLocation");
    if (detectLocation) {
      detectLocation.addEventListener("click", () => this.detectUserLocation());
    }

    // Mark all notifications as read
    const markAllRead = document.getElementById("markAllRead");
    if (markAllRead) {
      markAllRead.addEventListener("click", () => this.markAllAsRead());
    }

    // Keyboard shortcuts
    document.addEventListener("keydown", e => this.handleKeyboardShortcuts(e));

    // Resize handler - throttled to avoid forced reflow
    window.addEventListener("resize", () => {
      this.handleResize();
    });

    // Scroll handler
    window.addEventListener("scroll", () => this.handleScroll());
  }

  // Theme Management
  initTheme() {
    const savedTheme = localStorage.getItem("tro365-theme");
    const systemTheme = window.matchMedia("(prefers-color-scheme: dark)")
      .matches
      ? "dark"
      : "light";
    const theme = savedTheme || "auto";

    this.setTheme(theme);

    // Listen for system theme changes
    window
      .matchMedia("(prefers-color-scheme: dark)")
      .addEventListener("change", e => {
        if (document.documentElement.getAttribute("data-theme") === "auto") {
          this.applyTheme(e.matches ? "dark" : "light");
        }
      });
  }

  toggleTheme() {
    const currentTheme = document.documentElement.getAttribute("data-theme");
    let newTheme;

    // Simple toggle between light and dark only
    if (currentTheme === "dark") {
      newTheme = "light";
    } else {
      newTheme = "dark";
    }

    this.setTheme(newTheme);
  }

  setTheme(theme) {
    localStorage.setItem("tro365-theme", theme);
    document.documentElement.setAttribute("data-theme", theme);

    if (theme === "auto") {
      const systemTheme = window.matchMedia("(prefers-color-scheme: dark)")
        .matches
        ? "dark"
        : "light";
      this.applyTheme(systemTheme);
    } else {
      this.applyTheme(theme);
    }
  }

  applyTheme(theme) {
    document.documentElement.style.colorScheme = theme;

    // Update meta theme-color
    const metaThemeColor = document.querySelector('meta[name="theme-color"]');
    if (metaThemeColor) {
      metaThemeColor.content = theme === "dark" ? "#1a1d29" : "#0d6efd";
    }
  }

  // Search Management
  initSearch() {
    this.loadProvinces();
    this.initSearchSuggestions();
    this.initPriceSlider();
  }

  openSearch() {
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

  closeSearch() {
    const searchOverlay = document.getElementById("searchOverlay");
    if (searchOverlay) {
      searchOverlay.classList.remove("active");
      document.body.style.overflow = "";
    }
  }

  async loadProvinces() {
    try {
      // Use cached data from Tro365Common if available
      let provinces;
      if (
        window.Tro365Common &&
        window.Tro365Common._cache &&
        window.Tro365Common._cache.provinces
      ) {
        provinces = window.Tro365Common._cache.provinces;
      } else {
        // Wait for preloaded data or fetch if not available
        provinces = await new Promise((resolve, reject) => {
          if (
            window.Tro365Common &&
            window.Tro365Common._cache &&
            window.Tro365Common._cache.provinces
          ) {
            resolve(window.Tro365Common._cache.provinces);
          } else {
            // Listen for preloaded data
            const handleProvincesLoaded = event => {
              window.removeEventListener(
                "provincesLoaded",
                handleProvincesLoaded
              );
              resolve(event.detail);
            };
            window.addEventListener("provincesLoaded", handleProvincesLoaded);

            // Fallback timeout in case preload fails
            setTimeout(() => {
              window.removeEventListener(
                "provincesLoaded",
                handleProvincesLoaded
              );
              fetch("/api/locations/provinces")
                .then(response => response.json())
                .then(data => {
                  if (window.Tro365Common && window.Tro365Common._cache) {
                    window.Tro365Common._cache.provinces = data;
                  }
                  resolve(data);
                })
                .catch(reject);
            }, 1000);
          }
        });
      }

      this.populateProvinceSelects(provinces);
    } catch (error) {
      console.error("Error loading provinces:", error);
    }
  }

  populateProvinceSelects(provinces) {
    const provinceSelects = document.querySelectorAll(
      "#searchProvince, #quick-search-province"
    );

    provinceSelects.forEach(select => {
      // Use DocumentFragment for better performance
      const fragment = document.createDocumentFragment();
      const defaultOption = document.createElement("option");
      defaultOption.value = "";
      defaultOption.textContent = "Chọn tỉnh/thành";
      fragment.appendChild(defaultOption);

      // Only load popular provinces initially to reduce DOM size
      const popularProvinces = this.getPopularProvinces(provinces);

      popularProvinces.forEach(province => {
        const option = document.createElement("option");
        option.value = province.ID;
        option.textContent = province.TenTT;
        fragment.appendChild(option);
      });

      // Add "Load more" option if there are more provinces
      if (provinces.length > popularProvinces.length) {
        const loadMoreOption = document.createElement("option");
        loadMoreOption.value = "load_more";
        loadMoreOption.textContent = "--- Xem thêm tỉnh thành ---";
        loadMoreOption.disabled = true;
        fragment.appendChild(loadMoreOption);
      }

      // Clear and append all at once
      select.innerHTML = "";
      select.appendChild(fragment);

      // Store full provinces list for later use
      select._fullProvinces = provinces;
      select._popularOnly = true;
    });

    // Bind province change event
    provinceSelects.forEach(select => {
      select.addEventListener("change", e =>
        this.loadDistricts(e.target.value)
      );

      // Add focus event to load all provinces when user interacts
      select.addEventListener("focus", e => {
        if (e.target._popularOnly) {
          this.loadAllProvinces(e.target);
        }
      });
    });
  }

  getPopularProvinces(provinces) {
    // List of popular province codes (major cities)
    const popularCodes = [
      "01", // Hà Nội
      "79", // TP. Hồ Chí Minh
      "48", // Đà Nẵng
      "31", // Hải Phòng
      "92", // Cần Thơ
      "36", // Nam Định
      "33", // Hưng Yên
      "77", // Bà Rịa - Vũng Tàu
      "74", // Bình Dương
      "75", // Đồng Nai
    ];

    return provinces
      .filter(province => popularCodes.includes(province.ID))
      .slice(0, 10); // Limit to 10 popular provinces
  }

  loadAllProvinces(select) {
    if (!select._fullProvinces || !select._popularOnly) return;

    const fragment = document.createDocumentFragment();
    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.textContent = "Chọn tỉnh/thành";
    fragment.appendChild(defaultOption);

    select._fullProvinces.forEach(province => {
      const option = document.createElement("option");
      option.value = province.ID;
      option.textContent = province.TenTT;
      fragment.appendChild(option);
    });

    select.innerHTML = "";
    select.appendChild(fragment);
    select._popularOnly = false;
  }

  async loadDistricts(provinceId) {
    const districtSelect = document.getElementById("searchDistrict");
    const wardSelect = document.getElementById("searchWard");

    if (!districtSelect || !provinceId) return;

    try {
      const response = await fetch(
        `/api/locations/districts?province_id=${provinceId}`
      );
      const districts = await response.json();

      districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
      districtSelect.disabled = false;

      districts.forEach(district => {
        const option = document.createElement("option");
        option.value = district.ID;
        option.textContent = district.TenQH;
        districtSelect.appendChild(option);
      });

      // Reset ward select
      if (wardSelect) {
        wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
        wardSelect.disabled = true;
      }

      // Bind district change event
      districtSelect.addEventListener("change", e =>
        this.loadWards(e.target.value)
      );
    } catch (error) {
      console.error("Error loading districts:", error);
    }
  }

  async loadWards(districtId) {
    const wardSelect = document.getElementById("searchWard");

    if (!wardSelect || !districtId) return;

    try {
      const response = await fetch(
        `/api/locations/wards?district_id=${districtId}`
      );
      const wards = await response.json();

      wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
      wardSelect.disabled = false;

      wards.forEach(ward => {
        const option = document.createElement("option");
        option.value = ward.ID;
        option.textContent = ward.TenXP;
        wardSelect.appendChild(option);
      });
    } catch (error) {
      console.error("Error loading wards:", error);
    }
  }

  initSearchSuggestions() {
    const searchInput = document.querySelector(".search-input");
    const suggestionsContainer = document.getElementById("searchSuggestions");

    if (!searchInput || !suggestionsContainer) return;

    let debounceTimer;

    searchInput.addEventListener("input", e => {
      clearTimeout(debounceTimer);
      const query = e.target.value.trim();

      if (query.length < 2) {
        suggestionsContainer.innerHTML = "";
        return;
      }

      debounceTimer = setTimeout(() => {
        this.fetchSearchSuggestions(query);
      }, 300);
    });
  }

  async fetchSearchSuggestions(query) {
    try {
      const response = await fetch(
        `/api/posts/suggestions?q=${encodeURIComponent(query)}`
      );
      const data = await response.json();

      if (data.success && data.data.suggestions) {
        this.displaySearchSuggestions(data.data.suggestions);
      } else {
        this.clearSearchSuggestions();
      }
    } catch (error) {
      console.error("Error fetching search suggestions:", error);
      this.clearSearchSuggestions();
    }
  }

  displaySearchSuggestions(suggestions) {
    const suggestionsContainer = document.getElementById("searchSuggestions");
    if (!suggestionsContainer) return;

    if (suggestions.length === 0) {
      this.clearSearchSuggestions();
      return;
    }

    const suggestionsHTML = suggestions
      .map(
        suggestion => `
      <div class="suggestion-item" data-suggestion="${suggestion.text}" data-type="${suggestion.type}">
        <i class="${suggestion.icon} suggestion-icon"></i>
        <span class="suggestion-text">${suggestion.text}</span>
        <span class="suggestion-count">${suggestion.count}</span>
      </div>
    `
      )
      .join("");

    suggestionsContainer.innerHTML = `
      <div class="suggestions-list">
        ${suggestionsHTML}
      </div>
    `;

    // Add click handlers for suggestions
    suggestionsContainer.querySelectorAll(".suggestion-item").forEach(item => {
      item.addEventListener("click", () => {
        const searchInput = document.querySelector(".search-input");
        if (searchInput) {
          searchInput.value = item.dataset.suggestion;
          this.clearSearchSuggestions();

          // Trigger search
          const searchForm = searchInput.closest("form");
          if (searchForm) {
            searchForm.dispatchEvent(new Event("submit"));
          }
        }
      });
    });

    suggestionsContainer.style.display = "block";
  }

  clearSearchSuggestions() {
    const suggestionsContainer = document.getElementById("searchSuggestions");
    if (suggestionsContainer) {
      suggestionsContainer.innerHTML = "";
      suggestionsContainer.style.display = "none";
    }
  }

  initPriceSlider() {
    const priceSlider = document.querySelector(".range-slider");
    const priceFromInput = document.querySelector('input[name="price_from"]');
    const priceToInput = document.querySelector('input[name="price_to"]');

    if (!priceSlider) return;

    priceSlider.addEventListener("input", e => {
      const value = parseInt(e.target.value);
      if (priceFromInput) {
        priceFromInput.value = value;
      }
    });

    // Sync inputs with slider
    if (priceFromInput) {
      priceFromInput.addEventListener("input", e => {
        const value = parseInt(e.target.value) || 0;
        priceSlider.value = value;
      });
    }
  }

  handleAdvancedSearch(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const params = new URLSearchParams();

    for (const [key, value] of formData.entries()) {
      if (value) {
        params.append(key, value);
      }
    }

    // Redirect to search page with parameters
    window.location.href = `/search?${params.toString()}`;
  }

  resetSearchForm() {
    const form = document.getElementById("advancedSearchForm");
    if (form) {
      form.reset();

      // Reset disabled selects
      const districtSelect = document.getElementById("searchDistrict");
      const wardSelect = document.getElementById("searchWard");

      if (districtSelect) {
        districtSelect.disabled = true;
        districtSelect.innerHTML = '<option value="">Chọn quận/huyện</option>';
      }

      if (wardSelect) {
        wardSelect.disabled = true;
        wardSelect.innerHTML = '<option value="">Chọn phường/xã</option>';
      }
    }
  }

  // Check if Speech Recognition is supported
  isSpeechRecognitionSupported() {
    return "SpeechRecognition" in window || "webkitSpeechRecognition" in window;
  }

  // Get Speech Recognition constructor
  getSpeechRecognition() {
    return window.SpeechRecognition || window.webkitSpeechRecognition;
  }

  // Voice Search - Enhanced for mobile support
  startVoiceSearch() {
    if (!this.isSpeechRecognitionSupported()) {
      this.showToast(
        "Trình duyệt không hỗ trợ tìm kiếm bằng giọng nói",
        "error"
      );
      return;
    }

    // Check if HTTPS is required (most mobile browsers require it)
    if (location.protocol !== "https:" && location.hostname !== "localhost") {
      this.showToast(
        "Tìm kiếm bằng giọng nói yêu cầu kết nối HTTPS",
        "warning"
      );
      return;
    }

    const SpeechRecognition = this.getSpeechRecognition();
    const recognition = new SpeechRecognition();

    // Configuration for voice recognition
    recognition.lang = "vi-VN";
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    const voiceButton = document.getElementById("voiceSearch");
    const searchInput = document.querySelector(".search-input");

    recognition.onstart = () => {
      if (voiceButton) {
        voiceButton.classList.add("listening");
        voiceButton.innerHTML = '<i class="fas fa-microphone-slash"></i>';
        voiceButton.title = "Đang nghe... Click để dừng";
      }
      this.showToast("Đang nghe... Hãy nói từ khóa tìm kiếm", "info");
    };

    recognition.onresult = event => {
      const transcript = event.results[0][0].transcript;
      const confidence = event.results[0][0].confidence;

      if (searchInput) {
        searchInput.value = transcript;
        searchInput.dispatchEvent(new Event("input"));
        searchInput.focus();
      }

      // Handle confidence score - some browsers may not provide it or return undefined
      const confidenceScore =
        confidence !== undefined && confidence !== null
          ? Math.round(confidence * 100)
          : "N/A";

      this.showToast(
        `Đã nhận diện: "${transcript}" (độ tin cậy: ${confidenceScore}${
          confidenceScore !== "N/A" ? "%" : ""
        })`,
        "success"
      );
    };

    recognition.onend = () => {
      if (voiceButton) {
        voiceButton.classList.remove("listening");
        voiceButton.innerHTML = '<i class="fas fa-microphone"></i>';
        voiceButton.title = "Tìm kiếm bằng giọng nói";
      }
    };

    recognition.onerror = event => {
      console.error("Speech recognition error:", event.error);

      let errorMessage = "Lỗi nhận diện giọng nói";
      switch (event.error) {
        case "no-speech":
          errorMessage = "Không phát hiện giọng nói. Vui lòng thử lại";
          break;
        case "audio-capture":
          errorMessage =
            "Không thể truy cập microphone. Vui lòng kiểm tra quyền truy cập";
          break;
        case "not-allowed":
          errorMessage =
            "Quyền truy cập microphone bị từ chối. Vui lòng cho phép trong cài đặt trình duyệt";
          break;
        case "network":
          errorMessage = "Lỗi kết nối mạng. Vui lòng kiểm tra kết nối internet";
          break;
        case "service-not-allowed":
          errorMessage = "Dịch vụ nhận diện giọng nói không khả dụng";
          break;
      }

      this.showToast(errorMessage, "error");

      if (voiceButton) {
        voiceButton.classList.remove("listening");
        voiceButton.innerHTML = '<i class="fas fa-microphone"></i>';
        voiceButton.title = "Tìm kiếm bằng giọng nói";
      }
    };

    try {
      recognition.start();
    } catch (error) {
      console.error("Failed to start speech recognition:", error);
      this.showToast("Không thể khởi động nhận diện giọng nói", "error");
    }
  }

  // Location Detection - Enhanced for mobile support
  detectUserLocation() {
    if (!navigator.geolocation) {
      this.showToast("Trình duyệt không hỗ trợ định vị", "error");
      return;
    }

    // Check if HTTPS is required (geolocation requires HTTPS on most browsers)
    const isLocalhost =
      location.hostname === "localhost" ||
      location.hostname === "127.0.0.1" ||
      location.hostname === "::1";

    if (location.protocol !== "https:" && !isLocalhost) {
      this.showToast(
        "Định vị yêu cầu kết nối HTTPS để đảm bảo bảo mật",
        "warning"
      );
      return;
    }

    const detectButton = document.getElementById("detectLocation");
    if (detectButton) {
      detectButton.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Đang định vị...';
      detectButton.disabled = true;
    }

    this.showToast("Đang xác định vị trí của bạn...", "info");

    // Geolocation options
    const options = {
      enableHighAccuracy: true,
      timeout: 30000,
      maximumAge: 300000, // 5 minutes cache
    };

    navigator.geolocation.getCurrentPosition(
      position => {
        this.reverseGeocode(
          position.coords.latitude,
          position.coords.longitude
        );
      },
      error => {
        // Avoid noisy console errors; log only in debug with minimal info
        const errCode =
          error && typeof error.code !== "undefined" ? error.code : "UNKNOWN";
        const errMsg = error && error.message ? error.message : "";
        if (window.TRO365_DEBUG) {
          console.warn("Geolocation error:", {
            code: errCode,
            message: errMsg,
          });
        }

        let errorMessage = "Không thể xác định vị trí của bạn";
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage =
              "Quyền truy cập vị trí bị từ chối. Vui lòng cho phép trong cài đặt trình duyệt";
            break;
          case error.POSITION_UNAVAILABLE:
            errorMessage =
              "Thông tin vị trí không khả dụng. Vui lòng kiểm tra GPS/WiFi";
            break;
          case error.TIMEOUT:
            errorMessage = "Hết thời gian chờ định vị. Vui lòng thử lại";
            break;
          default:
            errorMessage = errMsg
              ? `Lỗi định vị: ${errMsg}`
              : "Không thể xác định vị trí của bạn";
            break;
        }

        this.showToast(errorMessage, "error");

        if (detectButton) {
          detectButton.innerHTML =
            '<i class="fas fa-location-arrow"></i> Vị trí hiện tại';
          detectButton.disabled = false;
        }
      },
      options
    );
  }

  async reverseGeocode(lat, lng) {
    try {
      const response = await fetch(
        `/api/locations/reverse-geocode?lat=${lat}&lng=${lng}`
      );

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const location = await response.json();

      if (location.success && location.province) {
        // Find all possible province dropdowns and select the best one
        const provinceDropdowns = [
          {
            province: document.getElementById("searchProvince"),
            district: document.getElementById("searchDistrict"),
            ward: document.getElementById("searchWard"),
            name: "searchProvince",
          },
          {
            province: document.getElementById("filterProvince"),
            district: document.getElementById("filterDistrict"),
            ward: document.getElementById("filterWard"),
            name: "filterProvince",
          },
          {
            province: document.querySelector("select[name='province']"),
            district: document.querySelector("select[name='district']"),
            ward: document.querySelector("select[name='ward']"),
            name: "generic province",
          },
          {
            province: document.querySelector(".province-select"),
            district: document.querySelector(".district-select"),
            ward: document.querySelector(".ward-select"),
            name: "class-based",
          },
        ];

        let selectedDropdown = null;

        // Find the best dropdown that has the required province option
        for (const dropdown of provinceDropdowns) {
          if (dropdown.province && dropdown.province.options) {
            const hasOption = Array.from(dropdown.province.options).some(
              opt => opt.value == location.province.ID
            );
            if (hasOption) {
              selectedDropdown = dropdown;
              console.log(`🎯 Found province option in ${dropdown.name}`);
              break;
            }
          }
        }

        // If no dropdown has the option, use the first available dropdown with most options
        if (!selectedDropdown) {
          for (const dropdown of provinceDropdowns) {
            if (
              dropdown.province &&
              dropdown.province.options &&
              dropdown.province.options.length > 2
            ) {
              selectedDropdown = dropdown;
              console.log(
                `📍 Using fallback dropdown ${dropdown.name} with ${dropdown.province.options.length} options`
              );
              break;
            }
          }
        }

        if (selectedDropdown && selectedDropdown.province) {
          const provinceSelect = selectedDropdown.province;
          const districtSelect = selectedDropdown.district;
          const wardSelect = selectedDropdown.ward;

          // Set the province value
          provinceSelect.value = location.province.ID;
          provinceSelect.dispatchEvent(new Event("change"));
          console.log(
            `✅ Set province: ${location.province.TenTT} (ID: ${location.province.ID})`
          );

          let locationText = location.province.TenTT;

          if (location.district) {
            setTimeout(() => {
              if (districtSelect) {
                // Wait for district options to load, then set value
                const checkDistrict = () => {
                  if (districtSelect.options.length > 1) {
                    const hasDistrictOption = Array.from(
                      districtSelect.options
                    ).some(opt => opt.value == location.district.ID);
                    if (hasDistrictOption) {
                      districtSelect.value = location.district.ID;
                      districtSelect.dispatchEvent(new Event("change"));
                      console.log(
                        `✅ Set district: ${location.district.TenQH} (ID: ${location.district.ID})`
                      );
                      locationText += `, ${location.district.TenQH}`;
                    } else {
                      console.log(
                        `⚠️ District option not found: ${location.district.TenQH} (ID: ${location.district.ID})`
                      );
                    }
                  } else {
                    // Retry after 500ms if options not loaded yet
                    setTimeout(checkDistrict, 500);
                  }
                };
                checkDistrict();

                if (location.ward) {
                  setTimeout(() => {
                    if (wardSelect) {
                      // Wait for ward options to load, then set value
                      const checkWard = () => {
                        if (wardSelect.options.length > 1) {
                          const hasWardOption = Array.from(
                            wardSelect.options
                          ).some(opt => opt.value == location.ward.ID);
                          if (hasWardOption) {
                            wardSelect.value = location.ward.ID;
                            console.log(
                              `✅ Set ward: ${location.ward.TenXP} (ID: ${location.ward.ID})`
                            );
                            locationText += `, ${location.ward.TenXP}`;
                          } else {
                            console.log(
                              `⚠️ Ward option not found: ${location.ward.TenXP} (ID: ${location.ward.ID})`
                            );
                          }
                        } else {
                          // Retry after 500ms if options not loaded yet
                          setTimeout(checkWard, 500);
                        }
                      };
                      checkWard();
                    }
                    this.showToast(
                      `Đã xác định vị trí: ${locationText}`,
                      "success"
                    );
                  }, 2000);
                } else {
                  this.showToast(
                    `Đã xác định vị trí: ${locationText}`,
                    "success"
                  );
                }
              }
            }, 500);
          } else {
            this.showToast(`Đã xác định vị trí: ${locationText}`, "success");
          }
        } else {
          console.log("❌ No suitable dropdown found for geolocation");
          this.showToast(
            "Không tìm thấy dropdown phù hợp để thiết lập vị trí",
            "warning"
          );
        }
      } else {
        this.showToast(
          "Không thể xác định địa chỉ từ vị trí hiện tại",
          "warning"
        );
      }
    } catch (error) {
      console.error("Reverse geocoding error:", error);
      this.showToast("Lỗi khi xác định địa chỉ từ vị trí", "error");
    } finally {
      const detectButton = document.getElementById("detectLocation");
      if (detectButton) {
        detectButton.innerHTML =
          '<i class="fas fa-location-arrow"></i> Vị trí hiện tại';
        detectButton.disabled = false;
      }
    }
  }

  // User Menu Management
  toggleUserMenu() {
    const userMenuWrapper = document.querySelector(".user-menu-wrapper");
    if (userMenuWrapper) {
      userMenuWrapper.classList.toggle("active");
    }
  }

  closeUserMenu() {
    const userMenuWrapper = document.querySelector(".user-menu-wrapper");
    if (userMenuWrapper) {
      userMenuWrapper.classList.remove("active");
    }
  }

  // Notifications Management
  initNotifications() {
    // Only initialize notification system if user is logged in
    if (window.Tro365Config && window.Tro365Config.isLoggedIn) {
      this.loadNotifications();
    } else {
      // Hide notification elements for guests
      const notificationToggle = document.getElementById("notificationToggle");
      const notificationWrapper = document.querySelector(
        ".notification-wrapper"
      );
      if (notificationToggle) notificationToggle.style.display = "none";
      if (notificationWrapper) notificationWrapper.style.display = "none";
    }
  }

  toggleNotifications() {
    const notificationWrapper = document.querySelector(".notification-wrapper");
    if (notificationWrapper) {
      notificationWrapper.classList.toggle("active");
      if (notificationWrapper.classList.contains("active")) {
        this.loadNotifications();
      }
    }
  }

  closeNotifications() {
    const notificationWrapper = document.querySelector(".notification-wrapper");
    if (notificationWrapper) {
      notificationWrapper.classList.remove("active");
    }
  }

  async loadNotifications() {
    try {
      const response = await fetch("/router/api/notifications.php");

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();

      if (!result.success) {
        throw new Error(result.message || "Failed to load notifications");
      }

      const notifications = result.data.notifications || [];
      const unreadCount = result.data.unread_count || 0;

      const notificationList = document.getElementById("notificationList");
      const notificationBadge = document.getElementById("notificationBadge");

      if (notificationList) {
        if (notifications.length === 0) {
          notificationList.innerHTML = `
            <div class="notification-empty">
              <i class="fas fa-bell-slash"></i>
              <p>Không có thông báo mới</p>
            </div>
          `;
        } else {
          notificationList.innerHTML = notifications
            .map(
              notification => `
            <div class="notification-item ${notification.read ? "" : "unread"}">
              <div class="notification-content">
                <h6>${notification.title}</h6>
                <p>${notification.message}</p>
                <span class="notification-time">${notification.time}</span>
              </div>
            </div>
          `
            )
            .join("");
        }
      }

      if (notificationBadge) {
        if (unreadCount > 0) {
          notificationBadge.textContent =
            unreadCount > 99 ? "99+" : unreadCount;
          notificationBadge.style.display = "flex";
        } else {
          notificationBadge.style.display = "none";
        }
      }
    } catch (error) {
      console.error("Error loading notifications:", error);
    }
  }

  // Location API Integration
  initLocationAPI() {
    // Initialize location-related functionality
    if (window.TRO365_DEBUG) {
      console.log("Location API initialized");
    }
  }

  async markAsRead(notificationId) {
    try {
      const response = await fetch(
        `/router/api/notifications.php/${notificationId}`,
        {
          method: "PUT",
          headers: {
            "Content-Type": "application/json",
          },
        }
      );

      if (response.ok) {
        // Reload notifications to update UI
        this.loadNotifications();
      }
    } catch (error) {
      console.error("Error marking notification as read:", error);
    }
  }

  async deleteNotification(notificationId) {
    if (!confirm("Bạn có chắc muốn xóa thông báo này?")) {
      return;
    }

    try {
      const response = await fetch(
        `/router/api/notifications.php/${notificationId}`,
        {
          method: "DELETE",
        }
      );

      if (response.ok) {
        // Reload notifications to update UI
        this.loadNotifications();
      }
    } catch (error) {
      console.error("Error deleting notification:", error);
    }
  }

  async markAllAsRead() {
    try {
      const response = await fetch("/router/api/notifications.php", {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
      });

      if (response.ok) {
        // Reload notifications to update UI
        this.loadNotifications();
      }
    } catch (error) {
      console.error("Error marking all as read:", error);
    }
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  getNotificationTypeIcon(type) {
    switch (type) {
      case 1:
        return '<i class="fas fa-info-circle"></i>';
      case 2:
        return '<i class="fas fa-exclamation-circle"></i>';
      case 3:
        return '<i class="fas fa-exclamation-triangle"></i>';
      default:
        return '<i class="fas fa-bell"></i>';
    }
  }

  setActiveNavigation() {
    const currentPath = window.location.pathname;

    // Set active state for main navigation links
    const mainNavLinks = document.querySelectorAll(
      ".navbar-nav-desktop .nav-link, .mobile-bottom-nav .nav-link"
    );
    mainNavLinks.forEach(link => {
      const href = link.getAttribute("href");
      if (href && this.isActivePath(currentPath, href)) {
        link.classList.add("active");
      } else {
        link.classList.remove("active");
      }
    });

    // Set active state for dropdown links
    const dropdownLinks = document.querySelectorAll(
      ".user-dropdown .dropdown-item"
    );
    dropdownLinks.forEach(link => {
      const href = link.getAttribute("href");
      if (href && this.isActivePath(currentPath, href)) {
        link.classList.add("active");
        // Remove highlight class if it exists
        link.classList.remove("highlight");
      } else {
        link.classList.remove("active");
        // Restore highlight class for specific items
        if (href === "/seller/posts/create") {
          link.classList.add("highlight");
        }
      }
    });
  }

  isActivePath(currentPath, linkPath) {
    // Exact match only - no sub-path matching
    return currentPath === linkPath;
  }

  // Toast notification system (delegates to unified TroToast)
  showToast(message, type = "info", duration = 5000) {
    if (window.TroToast && typeof window.TroToast.show === "function") {
      window.TroToast.show({ message, type, duration });
      return;
    }
    // Fallback (should rarely be needed)
    alert(message);
  }

  getToastIcon(type) {
    switch (type) {
      case "success":
        return "fas fa-check-circle";
      case "error":
        return "fas fa-exclamation-circle";
      case "warning":
        return "fas fa-exclamation-triangle";
      case "info":
      default:
        return "fas fa-info-circle";
    }
  }
}
