/* سرویس‌ورکر مدیریت مش: فقط فایل‌های ثابت کش می‌شوند؛ صفحات و داده‌ها همیشه از سرور می‌آیند */
const CACHE = 'mm-static-v1';
const OFFLINE = 'assets/offline.html';

self.addEventListener('install', e => {
    e.waitUntil(caches.open(CACHE).then(c => c.add(OFFLINE)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', e => {
    e.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', e => {
    const req = e.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== location.origin) return;
    if (url.pathname.includes('/assets/') && !url.pathname.endsWith('offline.html')) {
        e.respondWith(caches.match(req).then(hit => hit || fetch(req).then(res => {
            if (res.ok) { const copy = res.clone(); caches.open(CACHE).then(c => c.put(req, copy)); }
            return res;
        })));
        return;
    }
    if (req.mode === 'navigate') {
        e.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
    }
});
