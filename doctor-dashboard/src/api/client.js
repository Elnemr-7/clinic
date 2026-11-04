import axios from 'axios';

const client = axios.create({
  baseURL: '/api/clinic',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

client.interceptors.request.use((config) => {
  const token = sessionStorage.getItem('clinic_token') || localStorage.getItem('clinic_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('clinic_token');
      sessionStorage.removeItem('clinic_token');
      sessionStorage.removeItem('is_impersonating');
      if (window.location.pathname !== '/login' && !window.location.pathname.startsWith('/tv/')) {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export default client;
