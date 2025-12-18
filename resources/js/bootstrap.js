import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    const reverbHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
    const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? 8081);
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
        ...(csrfToken
            ? {
                  auth: {
                      headers: {
                          'X-CSRF-TOKEN': csrfToken,
                      },
                  },
              }
            : {}),
    });
}
