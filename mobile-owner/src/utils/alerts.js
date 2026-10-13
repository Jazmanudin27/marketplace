import Swal from 'sweetalert2';

// Custom themed toast and dialogs for Mobile ERP
const Toast = Swal.mixin({
  toast: true,
  position: 'top',
  showConfirmButton: false,
  timer: 2500,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer);
    toast.addEventListener('mouseleave', Swal.resumeTimer);
  }
});

const VALID_ICONS = ['success', 'error', 'warning', 'info', 'question'];

export const showToast = (arg1, arg2) => {
  let icon = 'success';
  let title = '';

  if (arg1 && arg2) {
    if (VALID_ICONS.includes(arg1)) {
      icon = arg1;
      title = arg2;
    } else if (VALID_ICONS.includes(arg2)) {
      icon = arg2;
      title = arg1;
    } else {
      title = `${arg1} ${arg2}`;
    }
  } else if (arg1) {
    title = arg1;
  }

  return Toast.fire({
    icon,
    title,
    background: '#0f172a',
    color: '#ffffff',
    customClass: {
      popup: 'swal2-mobile-toast'
    }
  });
};

export const showAlert = ({
  icon = 'info',
  title = '',
  text = '',
  confirmButtonText = 'Mengerti',
  confirmButtonColor = '#4f46e5'
}) => {
  return Swal.fire({
    icon,
    title,
    text,
    confirmButtonText,
    confirmButtonColor,
    background: '#ffffff',
    customClass: {
      popup: 'swal2-mobile-popup',
      title: 'swal2-mobile-title',
      htmlContainer: 'swal2-mobile-text',
      confirmButton: 'swal2-mobile-btn'
    }
  });
};

export const showConfirm = ({
  icon = 'question',
  title = 'Konfirmasi',
  text = '',
  confirmButtonText = 'Ya, Lanjutkan',
  cancelButtonText = 'Batal',
  confirmButtonColor = '#ef4444',
  cancelButtonColor = '#94a3b8'
}) => {
  return Swal.fire({
    icon,
    title,
    text,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText,
    confirmButtonColor,
    cancelButtonColor,
    reverseButtons: true,
    focusCancel: true,
    background: '#ffffff',
    customClass: {
      popup: 'swal2-mobile-popup',
      title: 'swal2-mobile-title',
      htmlContainer: 'swal2-mobile-text',
      confirmButton: 'swal2-mobile-btn',
      cancelButton: 'swal2-mobile-cancel-btn'
    }
  });
};

export default Swal;
