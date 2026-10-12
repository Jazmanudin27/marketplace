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

export const showToast = (icon, title) => {
  return Toast.fire({
    icon,
    title,
    background: '#1e1b4b',
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
  confirmButtonText = 'OK',
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
      confirmButton: 'swal2-mobile-btn'
    }
  });
};

export const showConfirm = ({
  icon = 'warning',
  title = 'Konfirmasi',
  text = '',
  confirmButtonText = 'Ya, Lanjutkan',
  cancelButtonText = 'Batal',
  confirmButtonColor = '#ef4444',
  cancelButtonColor = '#64748b'
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
    background: '#ffffff',
    customClass: {
      popup: 'swal2-mobile-popup',
      title: 'swal2-mobile-title',
      confirmButton: 'swal2-mobile-btn',
      cancelButton: 'swal2-mobile-cancel-btn'
    }
  });
};

export default Swal;
