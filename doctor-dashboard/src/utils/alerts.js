import Swal from 'sweetalert2';

const customClass = {
  popup: 'rounded-3xl shadow-2xl border border-slate-200 text-right font-sans',
  title: 'text-base font-extrabold text-slate-900',
  htmlContainer: 'text-xs text-slate-600',
  confirmButton: 'px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-md transition mx-1.5',
  cancelButton: 'px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition mx-1.5',
};

export const showSuccess = (title, message = '') => {
  return Swal.fire({
    icon: 'success',
    title,
    text: message,
    confirmButtonText: 'حسناً',
    customClass,
    buttonsStyling: false,
  });
};

export const showError = (title, message = '') => {
  return Swal.fire({
    icon: 'error',
    title,
    text: message,
    confirmButtonText: 'إغلاق',
    customClass: {
      ...customClass,
      confirmButton: 'px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md transition mx-1.5',
    },
    buttonsStyling: false,
  });
};

export const showWarning = (title, message = '') => {
  return Swal.fire({
    icon: 'warning',
    title,
    text: message,
    confirmButtonText: 'موافق',
    customClass: {
      ...customClass,
      confirmButton: 'px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md transition mx-1.5',
    },
    buttonsStyling: false,
  });
};

export const showConfirm = async (title, message = '', confirmText = 'نعم، تابع', cancelText = 'إلغاء') => {
  const result = await Swal.fire({
    icon: 'question',
    title,
    text: message,
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: cancelText,
    customClass,
    buttonsStyling: false,
    reverseButtons: true,
  });
  return result.isConfirmed;
};

export const showToast = (message, icon = 'success') => {
  const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
  });
  return Toast.fire({
    icon,
    title: message,
  });
};

export default {
  success: showSuccess,
  error: showError,
  warning: showWarning,
  confirm: showConfirm,
  toast: showToast,
};
