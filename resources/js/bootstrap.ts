import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.axios.interceptors.response.use(function (response) {
    return response;
}, function (error) {
    console.log('Axios response error', error)

    if (error.response && +error.response.status === 419) {
        window.location.href = '/login'
    }

    return Promise.reject(error);
});

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

declare global {
    interface Window {
        Pusher: any;
        Echo: any;
    }
}
