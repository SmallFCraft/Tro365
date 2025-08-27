/**
 * Modern HTTP Client - Thay thế fetch/XMLHttpRequest rườm rà
 * Sử dụng Axios-like API với error handling tốt hơn
 */
class HttpClient {
  constructor() {
    this.baseURL = window.location.origin;
    this.timeout = 10000;
    this.defaultHeaders = {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    };
  }

  /**
   * GET request
   */
  async get(url, config = {}) {
    return this.request("GET", url, null, config);
  }

  /**
   * POST request
   */
  async post(url, data = null, config = {}) {
    return this.request("POST", url, data, config);
  }

  /**
   * PUT request
   */
  async put(url, data = null, config = {}) {
    return this.request("PUT", url, data, config);
  }

  /**
   * DELETE request
   */
  async delete(url, config = {}) {
    return this.request("DELETE", url, null, config);
  }

  /**
   * Main request method
   */
  async request(method, url, data = null, config = {}) {
    const fullUrl = url.startsWith("http") ? url : `${this.baseURL}${url}`;

    const options = {
      method,
      headers: { ...this.defaultHeaders, ...config.headers },
      signal: AbortSignal.timeout(config.timeout || this.timeout),
    };

    // Add data for POST/PUT requests
    if (data && ["POST", "PUT", "PATCH"].includes(method)) {
      if (data instanceof FormData) {
        delete options.headers["Content-Type"]; // Let browser set boundary
        options.body = data;
      } else {
        options.body = JSON.stringify(data);
      }
    }

    try {
      const response = await fetch(fullUrl, options);

      // Handle HTTP errors
      if (!response.ok) {
        throw new HttpError(
          response.status,
          response.statusText,
          await this.parseResponse(response)
        );
      }

      return await this.parseResponse(response);
    } catch (error) {
      if (error.name === "AbortError") {
        throw new HttpError(408, "Request Timeout", {
          message: "Request timed out",
        });
      }
      throw error;
    }
  }

  /**
   * Parse response based on content type
   */
  async parseResponse(response) {
    const contentType = response.headers.get("content-type");

    if (contentType?.includes("application/json")) {
      return await response.json();
    } else if (contentType?.includes("text/")) {
      return await response.text();
    } else {
      return await response.blob();
    }
  }

  /**
   * Set default headers
   */
  setHeaders(headers) {
    this.defaultHeaders = { ...this.defaultHeaders, ...headers };
  }

  /**
   * Set CSRF token
   */
  setCsrfToken(token) {
    this.setHeaders({ "X-CSRF-Token": token });
  }
}

/**
 * Custom HTTP Error class
 */
class HttpError extends Error {
  constructor(status, statusText, data = null) {
    super(`HTTP ${status}: ${statusText}`);
    this.name = "HttpError";
    this.status = status;
    this.statusText = statusText;
    this.data = data;
  }
}

// Global instance
window.http = new HttpClient();

// Set CSRF token if available
const csrfToken = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute("content");
if (csrfToken) {
  window.http.setCsrfToken(csrfToken);
}
