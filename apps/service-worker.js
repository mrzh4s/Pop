// APP Service Worker - Fixed Version
// Place this file as /service-worker.js in your web root

console.log('APP Service Worker loaded');

// Service Worker version for cache busting
const CACHE_VERSION = 'app-v1.0.0';
const CACHE_NAME = `app-cache-${CACHE_VERSION}`;

// Files to cache (optional - customize as needed)
const STATIC_CACHE_FILES = [
    '/',
    '/auth/signin',
    // Add other important static files here
];

// Install event - cache initial files
self.addEventListener('install', function(event) {
    console.log('APP Service Worker installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('APP Service Worker cache opened');
                // Only cache if files exist, don't fail if they don't
                return Promise.allSettled(
                    STATIC_CACHE_FILES.map(url => {
                        return fetch(url).then(response => {
                            if (response.ok) {
                                return cache.put(url, response);
                            }
                        }).catch(() => {
                            console.log('Could not cache:', url);
                        });
                    })
                );
            })
            .then(() => {
                console.log('APP Service Worker installed successfully');
                return self.skipWaiting(); // Activate immediately
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', function(event) {
    console.log('APP Service Worker activating...');
    
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    // Delete old caches
                    if (cacheName !== CACHE_NAME && cacheName.startsWith('app-cache-')) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('APP Service Worker activated successfully');
            return self.clients.claim(); // Take control immediately
        })
    );
});

// Fetch event - handle network requests
self.addEventListener('fetch', function(event) {
    const request = event.request;
    const url = new URL(request.url);
    
    // Only handle GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Handle different types of requests
    if (url.origin === self.location.origin) {
        // Same origin requests
        if (url.pathname.startsWith('/api/')) {
            // API requests - always fetch from network, handle auth redirects
            event.respondWith(handleApiRequest(request));
        } else if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/css/') || url.pathname.startsWith('/js/')) {
            // Static assets - cache first strategy
            event.respondWith(handleStaticAssets(request));
        } else {
            // Page requests - network first strategy
            event.respondWith(handlePageRequest(request));
        }
    }
    // Let browser handle external requests normally
});

// Handle API requests with auth redirect
async function handleApiRequest(request) {
    try {
        const response = await fetch(request, { 
            cache: "no-store",
            credentials: 'same-origin'
        });
        
        // Handle authentication redirects
        if (response.status === 401) {
            console.log('APP: Authentication required, redirecting to login');
            // Instead of redirecting, let the app handle it
            return response;
        }
        
        return response;
    } catch (error) {
        console.error('APP Service Worker: API request failed', error);
        
        // Return a proper error response for API calls
        return new Response(JSON.stringify({
            status: 'error',
            message: 'Network error occurred',
            offline: true
        }), {
            status: 503,
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
}

// Handle static assets with cache-first strategy
async function handleStaticAssets(request) {
    try {
        // Try cache first
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Fetch from network and cache
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.error('APP Service Worker: Static asset request failed', error);
        
        // Try to return from cache as fallback
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return a 404 if nothing else works
        return new Response('Asset not available offline', { status: 404 });
    }
}

// Handle page requests with network-first strategy
async function handlePageRequest(request) {
    try {
        // Try network first
        const networkResponse = await fetch(request, {
            credentials: 'same-origin'
        });
        
        // Handle authentication redirects for pages
        if (networkResponse.status === 401) {
            console.log('APP: Page requires authentication');
            return Response.redirect('/auth/signin', 302);
        }
        
        // Cache successful page responses
        if (networkResponse.ok && networkResponse.status < 300) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.error('APP Service Worker: Page request failed', error);
        
        // Try to return cached version
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            console.log('APP: Serving cached page');
            return cachedResponse;
        }
        
        // Return offline page or basic error
        return new Response(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>APP - Offline</title>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        text-align: center; 
                        padding: 50px; 
                        background: #f8f9fa;
                    }
                    .offline-container {
                        max-width: 400px;
                        margin: 0 auto;
                        background: white;
                        padding: 40px;
                        border-radius: 8px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    }
                    h1 { color: #dc3545; margin-bottom: 20px; }
                    p { color: #6c757d; margin-bottom: 20px; }
                    .retry-btn {
                        background: #007bff;
                        color: white;
                        border: none;
                        padding: 12px 24px;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 16px;
                    }
                    .retry-btn:hover { background: #0056b3; }
                </style>
            </head>
            <body>
                <div class="offline-container">
                    <h1>You're Offline</h1>
                    <p>Please check your internet connection and try again.</p>
                    <button class="retry-btn" onclick="window.location.reload()">
                        Try Again
                    </button>
                </div>
            </body>
            </html>
        `, {
            status: 503,
            headers: {
                'Content-Type': 'text/html'
            }
        });
    }
}

// Handle service worker updates
self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        console.log('APP Service Worker: Skipping waiting...');
        self.skipWaiting();
    }
});

// Periodic cleanup (optional)
self.addEventListener('periodicsync', function(event) {
    if (event.tag === 'cache-cleanup') {
        event.waitUntil(cleanupOldCaches());
    }
});

async function cleanupOldCaches() {
    const cacheNames = await caches.keys();
    const oldCaches = cacheNames.filter(name => 
        name.startsWith('app-cache-') && name !== CACHE_NAME
    );
    
    return Promise.all(
        oldCaches.map(cache => caches.delete(cache))
    );
}

console.log('APP Service Worker script loaded successfully');