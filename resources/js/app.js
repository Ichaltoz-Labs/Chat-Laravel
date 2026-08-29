import axios from 'axios';
import { initHome } from './home';
import { initRoom } from './room';

window.axios = axios;

const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    axios.defaults.headers.common['Accept'] = 'application/json';
}

initHome();
initRoom();