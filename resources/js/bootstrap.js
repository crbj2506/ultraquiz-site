import _ from 'lodash';
window._ = _;

import 'bootstrap';
import { Collapse, Dropdown } from 'bootstrap';

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Fallback defensivo para garantir funcionamento do menu principal.
document.addEventListener('DOMContentLoaded', () => {
	const navbarCollapseEl = document.getElementById('navbarSupportedContent');
	const navbarTogglerEl = document.querySelector('.navbar-toggler');

	if (navbarCollapseEl && navbarTogglerEl) {
		const navbarCollapse = Collapse.getOrCreateInstance(navbarCollapseEl, { toggle: false });
		navbarTogglerEl.addEventListener('click', (event) => {
			event.preventDefault();
			event.stopPropagation();
			navbarCollapse.toggle();
		});
	}

	document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((el) => {
		const dropdown = Dropdown.getOrCreateInstance(el, { autoClose: true });
		el.addEventListener('click', (event) => {
			event.preventDefault();
			event.stopPropagation();
			dropdown.toggle();
		});
	});
});

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });
