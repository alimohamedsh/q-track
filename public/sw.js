const CACHE_NAME = 'q-track-v1';
// لا نخزن صفحات HTML (خصوصاً /) حتى لا تعلق نسخة قديمة (welcome) في الكاش
const urlsToCache = [];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(urlsToCache))
  );
  self.skipWaiting();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  // لوحة الفني وكل مساراتها: دائماً من الشبكة وليس من الكاش
  if (url.pathname === '/technician' || url.pathname.startsWith('/technician/')) {
    event.respondWith(fetch(event.request));
    return;
  }
  // الصفحة الرئيسية: دائماً من الشبكة لتجنب تثبيت نسخة قديمة
  if (url.pathname === '/') {
    event.respondWith(fetch(event.request));
    return;
  }
  event.respondWith(
    caches.match(event.request).then((response) => response || fetch(event.request))
  );
});
