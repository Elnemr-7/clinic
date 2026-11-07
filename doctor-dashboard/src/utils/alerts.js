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

export const promptRefundOnCancel = async (patientName, amount) => {
  return Swal.fire({
    icon: 'warning',
    title: 'استرداد المبلغ وإلغاء الحجز',
    html: `
      <div class="text-right space-y-2 font-sans py-1" dir="rtl">
        <p class="text-xs text-slate-700 leading-relaxed">
          قام المريض <strong class="text-slate-900">${patientName || 'المريض'}</strong> بسداد مبلغ
          <strong class="text-emerald-600 font-mono text-sm">${amount} ج.م</strong>.
        </p>
        <p class="text-xs text-slate-500 font-medium">
          هل تريد استرداد هذا المبلغ نقداً للمريض وخصمه من الخزينة الآن؟
        </p>
      </div>
    `,
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonText: 'نعم، استرداد وخصم من الخزينة',
    denyButtonText: 'إلغاء الحجز بدون استرداد',
    cancelButtonText: 'تراجع (إبقاء الحجز)',
    customClass: {
      popup: 'rounded-3xl shadow-2xl border border-slate-200 text-right font-sans p-6',
      title: 'text-base font-extrabold text-slate-900',
      htmlContainer: 'text-xs text-slate-600 !pt-2 !pb-4',
      confirmButton: 'px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition mx-1 cursor-pointer',
      denyButton: 'px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md transition mx-1 cursor-pointer',
      cancelButton: 'px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition mx-1 cursor-pointer',
    },
    buttonsStyling: false,
    reverseButtons: true,
  });
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
  promptRefund: promptRefundOnCancel,
  toast: showToast,
};
