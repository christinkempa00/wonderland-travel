/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Service Worker
 * ================================================================
 * Handles caching and offline functionality
 * ================================================================
 */

const CACHE_NAME = 'travelms-cache-v1';
const OFFLINE_URL = '/offline.html';

// Assets to cache immediately on install
const PRECACHE_ASSETS = [
    '/',
    '/assets/css/style.css',
    '/assets/images/logo.png',
    '/manifest.json',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css'
];

// Install event - cache essential assets
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[ServiceWorker] Pre-caching assets');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => {
                console.log('[ServiceWorker] Installed successfully');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[ServiceWorker] Pre-cache failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => cacheName !== CACHE_NAME)
                        .map((cacheName) => {
                            console.log('[ServiceWorker] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        })
                );
            })
            .then(() => {
                console.log('[ServiceWorker] Activated successfully');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip API requests - always fetch from network
    if (url.pathname.startsWith('/api/')) {
        return;
    }
    
    // Skip cross-origin requests (except fonts and CDN)
    if (url.origin !== location.origin && 
        !url.origin.includes('fonts.googleapis.com') &&
        !url.origin.includes('fonts.gstatic.com') &&
        !url.origin.includes('cdnjs.cloudflare.com')) {
        return;
    }
    
    event.respondWith(
        handleFetch(request)
    );
});

/**
 * Handle fetch with strategy based on request type
 */
async function handleFetch(request) {
    const url = new URL(request.url);
    
    // Static assets - Cache First
    if (isStaticAsset(url)) {
        return cacheFirst(request);
    }
    
    // HTML pages - Network First
    if (request.headers.get('accept')?.includes('text/html')) {
        return networkFirst(request);
    }
    
    // Default - Stale While Revalidate
    return staleWhileRevalidate(request);
}

/**
 * Check if URL is a static asset
 */
function isStaticAsset(url) {
    const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg', '.woff', '.woff2', '.ttf'];
    return staticExtensions.some(ext => url.pathname.endsWith(ext));
}

/**
 * Cache First Strategy
 * Good for static assets that don't change often
 */
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.error('[ServiceWorker] Cache first failed:', error);
        return new Response('Offline', { status: 503 });
    }
}

/**
 * Network First Strategy
 * Good for HTML pages that need fresh content
 */
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('[ServiceWorker] Network first - falling back to cache');
        
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline page for navigation requests
        if (request.mode === 'navigate') {
            const offlineResponse = await caches.match(OFFLINE_URL);
            if (offlineResponse) {
                return offlineResponse;
            }
        }
        
        return new Response(getOfflineHTML(), {
            headers: { 'Content-Type': 'text/html' }
        });
    }
}

/**
 * Stale While Revalidate Strategy
 * Good for content that can be slightly stale
 */
async function staleWhileRevalidate(request) {
    const cachedResponse = await caches.match(request);
    
    const fetchPromise = fetch(request.clone())
        .then(async (networkResponse) => {
            // Only cache successful responses
            if (networkResponse && networkResponse.ok) {
                try {
                    const responseToCache = networkResponse.clone();
                    const cache = await caches.open(CACHE_NAME);
                    await cache.put(request, responseToCache);
                } catch (e) {
                    console.log('Cache put failed:', e);
                }
            }
            return networkResponse;
        })
        .catch((err) => {
            console.log('Fetch failed:', err);
            return cachedResponse;
        });
    
    return cachedResponse || fetchPromise;
}

/**
 * Get offline HTML page
 */
function getOfflineHTML() {
    return `
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - Travel Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #574314 0%, #c89b2c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 2rem;
        }
        .container {
            max-width: 400px;
        }
        .icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.8;
        }
        h1 {
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
        }
        p {
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        button {
            padding: 0.75rem 2rem;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        button:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📡</div>
        <h1>Anda Sedang Offline</h1>
        <p>Sepertinya Anda tidak terhubung ke internet. Periksa koneksi Anda dan coba lagi.</p>
        <button onclick="window.location.reload()">
            Coba Lagi
        </button>
    </div>
</body>
</html>
    `;
}

// Listen for messages from the main thread
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        caches.delete(CACHE_NAME).then(() => {
            console.log('[ServiceWorker] Cache cleared');
        });
    }
});

// Background Sync (for offline form submissions)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-orders') {
        event.waitUntil(syncOrders());
    }
});

/**
 * Sync pending orders when back online
 */
async function syncOrders() {
    // Implementation for background sync
    // This would process any queued order submissions
    console.log('[ServiceWorker] Syncing orders...');
}

// Push Notifications
self.addEventListener('push', (event) => {
    if (!event.data) return;
    
    const data = event.data.json();
    
    const options = {
        body: data.body || '',
        icon: '/assets/images/icons/icon-192x192.png',
        badge: '/assets/images/icons/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/'
        },
        actions: data.actions || []
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Travel Management', options)
    );
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    const url = event.notification.data?.url || '/';
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                // Check if there's already a window open
                for (const client of windowClients) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Open new window if none found
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

console.log('[ServiceWorker] Script loaded');
