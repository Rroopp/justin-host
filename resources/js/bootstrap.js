import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Set up CSRF token for Laravel
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Set up authentication token from localStorage or cookie
const authToken = localStorage.getItem('token') || getCookie('sanctum_token');
if (authToken) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`;
}

// Helper function to get cookie
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

// Update token on every request from cookie if available
window.axios.interceptors.request.use(function (config) {
    const token = localStorage.getItem('token') || getCookie('sanctum_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Register Alpine.js data functions globally
document.addEventListener('alpine:init', () => {
    // This will be populated by individual page scripts
    // Functions defined in blade templates will be available globally
});
