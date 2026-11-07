import axios from 'axios';
import router from '../router';
import { showToast } from '../utils/alerts';

const client = axios.create({
  baseURL: '/api/super-admin',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

client.interceptors.request.use((config) => {
  const token = localStorage.getItem('super_admin_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

client.interceptors.response.use(
  (response) => response,
  (error) => {
    // If caller explicitly requested to suppress global error handling
    if (error.config?.skipGlobalError) {
      return Promise.reject(error);
    }

    // 1. Network / Server Unreachable Error
    if (!error.response || error.code === 'ERR_NETWORK') {
      showToast('تعذر الاتصال بالخادم. يرجى التأكد من تشغيل الخادم والاتصال بالإنترنت.', 'error');
      return Promise.reject(error);
    }

    const { status, data } = error.response;

    // 2. Authentication 401
    if (status === 401) {
      localStorage.removeItem('super_admin_token');
      if (router.currentRoute?.value?.name !== 'login') {
        showToast('انتهت صلاحية جلسة المشرف العام، يرجى تسجيل الدخول', 'warning');
        router.push({ name: 'login' });
      }
      return Promise.reject(error);
    }

    // 3. Forbidden 403
    if (status === 403) {
      showToast(data?.message || 'غير مصرح لك بتنفيذ هذا الإجراء', 'error');
      return Promise.reject(error);
    }

    // 4. Validation Error 422
    if (status === 422) {
      let validationMsg = data?.message || 'بيانات غير صالحة';
      if (data?.errors && typeof data.errors === 'object') {
        const errorList = Object.values(data.errors).flat();
        if (errorList.length > 0) {
          validationMsg = errorList[0];
        }
      }
      showToast(validationMsg, 'warning');
      return Promise.reject(error);
    }

    // 5. Rate Limit 429
    if (status === 429) {
      showToast('تم تجاوز حد الطلبات المسموح به. يرجى الانتظار قليلاً.', 'warning');
      return Promise.reject(error);
    }

    // 6. Server Error 500+
    if (status >= 500) {
      showToast(data?.message || 'حدث خطأ في خادم النظام (500). يرجى المحاولة لاحقاً.', 'error');
      return Promise.reject(error);
    }

    return Promise.reject(error);
  }
);

export default client;
