// service worker para notificaciones push
/*
self.addEventListener('push', function (event) {
    const data = event.data.json().notification;

    event.waitUntil(self.registration.showNotification(data.title, {
        body: data.body,
        icon: '/assets/img/logo.png',
        image: '/assets/img/logo.png',
        tag: data.tag,
        data: {
            url: data.click_action
        },
        requireInteraction: data.requireInteraction,
        renotify: data.renotify,
        vibrate: data.vibrate
    }));
});

self.addEventListener('notificationclick', function (event) {
    const url = event.notification.data.url;
    event.notification.close();
    event.waitUntil(clients.openWindow(url));
});*/


// service worker para notificaciones push

self.addEventListener('push', function (event) {
    let data = {};

    try {
        const payload = event.data.json();
        data = payload.notification || payload.data || {};
    } catch (e) {
        console.error('Push recibido con payload inválido:', e);
        data = { title: 'Notificación', body: '' };
    }

    const opciones = {
        body: data.body || '',
        icon: data.icon || '/assets/img/logo.png',
        image: data.image || '/assets/img/logo.png',
        tag: data.tag,
        data: { url: data.click_action || '/' },
        requireInteraction: !!data.requireInteraction,
        renotify: !!data.renotify && !!data.tag, // renotify solo tiene sentido con tag
        vibrate: data.vibrate || [200, 100, 200]
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Notificación', opciones)
    );
});

self.addEventListener('notificationclick', function (event) {
    const url = (event.notification.data && event.notification.data.url) || '/';
    event.notification.close();

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.indexOf(self.location.origin) === 0 && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) return clients.openWindow(url);
        })
    );
});