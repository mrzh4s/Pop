<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 9999;">
    <div id="mainToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fad fa-circle-info me-2" id="toastIcon"></i>
            <strong class="me-auto" id="toastTitle">Notification</strong>
            <button type="button" class="btn-close me-1" data-bs-dismiss="toast" aria-label="Close">
            </button>
        </div>
        <div class="toast-body" id="toastMessage">
            Message content goes here
        </div>
    </div>
</div>

<script>
/**
 * Show Toast Notification
 * @param {string} type - Type of toast: 'success', 'error', 'warning', 'info'
 * @param {string} title - Toast title
 * @param {string} message - Toast message
 * @param {number} duration - Auto hide duration in milliseconds (default: 3000)
 */
function showToast(type, title, message, duration = 3000) {
    const toastEl = document.getElementById('mainToast');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');
    
    // Remove previous type classes
    toastEl.classList.remove('toast-success', 'toast-error', 'toast-warning', 'toast-info');
    
    // Set icon based on type
    const icons = {
        success: 'fa-circle-check text-success',
        error: 'fa-circle-xmark text-danger',
        warning: 'fa-triangle-exclamation text-warning',
        info: 'fa-circle-info text-info'
    };
    
    // Apply new settings
    toastEl.classList.add(`toast-${type}`);
    toastIcon.className = `fad me-2 fa-lg ${icons[type] || icons.info}`;
    toastTitle.textContent = title;
    toastMessage.textContent = message;
    
    // Initialize and show toast
    const toast = new bootstrap.Toast(toastEl, {
        autohide: true,
        delay: duration
    });
    
    toast.show();
}
</script>