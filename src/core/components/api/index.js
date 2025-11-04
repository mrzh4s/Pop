const apps = window.location.origin;

// Service Worker Registration
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js')
        .catch(() => navigator.serviceWorker.register('./service-worker.js'))
        .catch(() => console.log('Service Worker not available'));
}

// Core API Helper - Only API, CSRF, and Cookies
const ApiHelper = (() => {
    
    // ============== COOKIE MANAGEMENT ==============
    const CookieManager = {
        get: (name) => {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            return parts.length === 2 ? parts.pop().split(';').shift() : null;
        },

        set: (name, value, options = {}) => {
            const defaults = {
                path: '/',
                secure: window.location.protocol === 'https:',
                sameSite: 'Strict'
            };
            
            const config = { ...defaults, ...options };
            let cookieString = `${name}=${value}`;
            
            Object.entries(config).forEach(([key, val]) => {
                if (val === true) {
                    cookieString += `; ${key}`;
                } else if (val !== false && val !== null) {
                    cookieString += `; ${key}=${val}`;
                }
            });
            
            document.cookie = cookieString;
        },

        delete: (name, path = '/') => {
            document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=${path}`;
        },

        exists: (name) => CookieManager.get(name) !== null
    };

    // ============== CSRF TOKEN MANAGEMENT ==============
    const getCSRFToken = () => {
        // Priority: meta tag > cookie > global config
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag && metaTag.content) return metaTag.content;
        
        const cookieToken = CookieManager.get('csrf_token');
        if (cookieToken) return cookieToken;
        
        return window.APP_CONFIG?.CSRF_TOKEN || null;
    };

    const updateCSRFToken = (newToken) => {
        if (!newToken) return;

        // Update meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) metaTag.content = newToken;

        // Update global config
        if (window.APP_CONFIG) window.APP_CONFIG.CSRF_TOKEN = newToken;

        // Store in cookie
        CookieManager.set('csrf_token', newToken, {
            maxAge: 3600, // 1 hour
            secure: window.location.protocol === 'https:',
            sameSite: 'Strict'
        });
    };

    const addCSRFHeaders = (headers = {}) => {
        const token = getCSRFToken();
        if (token) {
            headers['X-CSRF-TOKEN'] = token;
            headers['X-Requested-With'] = 'XMLHttpRequest';
        }
        return headers;
    };

    // ============== BASIC TOAST NOTIFICATIONS ==============
    const showToast = (type, message, title = '') => {
        if (window.KTToast) {
            const variantMap = { 'success': 'success', 'error': 'danger', 'warning': 'warning', 'info': 'info' };
            const iconMap = {
                'success': '<i class="far fa-check text-success text-xl"></i>',
                'error': '<i class="far fa-times text-danger text-xl"></i>',
                'warning': '<i class="far fa-triangle-exclamation text-warning text-xl"></i>',
                'info': '<i class="far fa-info-circle text-info text-xl"></i>'
            };

            window.KTToast.show({
                message: title ? `${title}: ${message}` : message,
                variant: variantMap[type] || 'info',
                icon: iconMap[type],
                progress: true,
                pauseOnHover: true,
                timeout: type === 'success' ? 3000 : 5000
            });
        } else if (window.Swal) {
            const iconMap = { 'success': 'success', 'error': 'error', 'warning': 'warning', 'info': 'info' };
            window.Swal.fire({
                icon: iconMap[type] || 'info',
                title: title || type.charAt(0).toUpperCase() + type.slice(1),
                text: message,
                confirmButtonClass: 'btn btn-primary',
                timer: type === 'success' ? 3000 : undefined
            });
        } else if (typeof window.toastr !== 'undefined') {
            window.toastr[type](message, title);
        } else {
            console.warn(`Toast (${type}): ${title} - ${message}`);
        }
    };

    return {
        // CSRF functions
        getCSRFToken,
        updateCSRFToken,
        addCSRFHeaders,
        
        // Cookie functions  
        CookieManager,
        
        // Utility
        showToast
    };
})();

// ============== API REQUEST CLASS ==============
class ApiRequest {
    constructor(method, endpoint, data = null) {
        this.method = method;
        this.endpoint = endpoint;
        this.requestData = data;
        this.options = {};
        this.retryCount = 0;
        this.maxRetries = 3;
    }

    headers(headers) {
        const csrfHeaders = ApiHelper.addCSRFHeaders();
        this.options.headers = { ...csrfHeaders, ...this.options.headers, ...headers };
        return this;
    }

    timeout(ms) {
        this.options.timeout = ms;
        return this;
    }

    config(options) {
        this.options = { ...this.options, ...options };
        return this;
    }

    skipErrorHandling() {
        this.options.skipErrorHandling = true;
        return this;
    }

    _execute() {
        const url = `${apps}/api/${this.endpoint}`;
        const processedData = this._processData(this.requestData);

        const config = {
            method: this.method,
            url,
            data: processedData,
            withCredentials: true, // Include cookies
            ...this.options
        };

        // Add CSRF headers for requests with data
        if (['POST', 'PUT', 'PATCH'].includes(this.method.toUpperCase())) {
            const defaultHeaders = ApiHelper.addCSRFHeaders({ 'Content-Type': 'application/json' });
            config.headers = { ...defaultHeaders, ...config.headers };
        } else {
            config.headers = { ...ApiHelper.addCSRFHeaders(), ...config.headers };
        }

        return axios(config)
            .then(response => {
                // Auto-update CSRF token from server responses
                if (response.data && response.data.csrf_token) {
                    ApiHelper.updateCSRFToken(response.data.csrf_token);
                }
                return response.data;
            })
            .catch(error => {
                if (!this.options.skipErrorHandling) {
                    this._handleError(error);
                }

                if (this.shouldRetry(error)) {
                    return this.retry();
                }

                throw error;
            });
    }

    _processData(data) {
        if (!data) return {};
        if (typeof data === 'object' && data.constructor === Object) return data;
        
        // Handle FormData
        if (data instanceof FormData) {
            const jsonObject = {};
            for (let [key, value] of data.entries()) {
                jsonObject[key] = value;
            }
            return jsonObject;
        }
        
        return data;
    }

    _handleError(error) {
        console.error('API Error:', error);
        
        let message = 'An error occurred';
        if (error.response && error.response.data && error.response.data.message) {
            message = error.response.data.message;
        } else if (error.message) {
            message = error.message;
        }
        
        ApiHelper.showToast('error', message);
    }

    shouldRetry(error) {
        if (this.retryCount >= this.maxRetries) return false;
        if (error.response && [401, 403, 422].includes(error.response.status)) return false;
        return !error.response || error.response.status >= 500;
    }

    retry() {
        this.retryCount++;
        const delay = Math.min(1000 * Math.pow(2, this.retryCount - 1), 10000);
        
        return new Promise(resolve => {
            setTimeout(() => resolve(this._execute()), delay);
        });
    }

    then(onFulfilled, onRejected) { return this._execute().then(onFulfilled, onRejected); }
    catch(onRejected) { return this._execute().catch(onRejected); }
    finally(onFinally) { return this._execute().finally(onFinally); }
}

// ============== SIMPLE API OBJECT ==============
const api = {
    // HTTP methods
    get: (endpoint) => new ApiRequest('GET', endpoint),
    post: (endpoint, data = null) => new ApiRequest('POST', endpoint, data),
    put: (endpoint, data = null) => new ApiRequest('PUT', endpoint, data),
    patch: (endpoint, data = null) => new ApiRequest('PATCH', endpoint, data),
    delete: (endpoint) => new ApiRequest('DELETE', endpoint),

    // Utilities
    utils: {
        refreshCSRF: async () => {
            try {
                const response = await api.get('csrf-token').skipErrorHandling();
                if (response.token) {
                    ApiHelper.updateCSRFToken(response.token);
                }
                return response.token;
            } catch (error) {
                console.error('Failed to refresh CSRF token:', error);
                return null;
            }
        }
    }
};

// ============== GLOBAL SETUP ==============
window.ApiHelper = ApiHelper;
window.api = api;

// Auto-refresh CSRF token every 15 minutes
setInterval(() => {
    api.utils.refreshCSRF();
}, 15 * 60 * 1000);

// Module exports
export { ApiHelper, api, ApiRequest };