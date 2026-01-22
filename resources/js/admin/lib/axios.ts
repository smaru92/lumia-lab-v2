import axios from 'axios';

const api = axios.create({
    baseURL: '/api/admin',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

api.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        // /user 엔드포인트는 인증 체크용이므로 리다이렉트하지 않음
        const isAuthCheck = error.config?.url === '/user';
        if (error.response?.status === 401 && !isAuthCheck) {
            window.location.href = '/admin/login';
        }
        return Promise.reject(error);
    }
);

export default api;
