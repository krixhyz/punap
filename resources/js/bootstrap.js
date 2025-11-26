import './bootstrap';
import Alpine from 'alpinejs';
import axios from 'axios';
import Echo from 'laravel-echo';



window.Alpine = Alpine;
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ✅ Start Alpine
Alpine.start();

const userMeta = document.querySelector('meta[name="user"]');
if (userMeta) {
    window.user = JSON.parse(userMeta.content);

    window.Echo.private(`App.Models.User.${window.user.id}`)
        .notification((notification) => {
            console.log("🔔 New Notification:", notification);

        });
}


const reverbPort = Number(import.meta.env.VITE_REVERB_PORT) || 8080;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: null,
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// ✅ Listen for private events if user is logged in
if (window.user && window.user.id) {
    window.Echo.private(`user.${window.user.id}`)
        .listen('.swap.requested', (e) => {
            console.log("New Swap Request", e);
        })
        .listen('.swap.accepted', (e) => {
            console.log("Swap Accepted", e);
        })
        .listen('.swap.rejected', (e) => {
            console.log("Swap Rejected", e);
        });
}

// (Optional) If you have additional Echo setup
import './echo';
