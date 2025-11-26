import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// listen to user private channel notifications
window.Echo.private(`App.Models.User.${window.Laravel.userId}`)
    .notification((notification) => {
        console.log('new notification', notification);

        // increment badge
        const badge = document.getElementById('notification-count');
        if (badge) {
            const current = parseInt(badge.innerText) || 0;
            badge.innerText = current + 1;
        }

        // optionally append into notifications list
        const container = document.getElementById('notifications-container');
        if (container) {
            const el = document.createElement('div');
            el.className = 'p-4 border-b';
            el.innerHTML = `<p class="text-sm">${notification.message}</p>
                            <a href="/swap-requests/${notification.swap_request_id}" class="text-xs text-blue-600">View</a>`;
            container.prepend(el);
        }

        // show a toast
        if (typeof showToast === 'function') {
            showToast(notification.message);
        }
    });
