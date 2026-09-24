/* Toast notifications */
function showToast(message, type) {
    type = type || 'info';
    var container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position:fixed;top:90px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px;max-width:340px;';
        document.body.appendChild(container);
    }

    var toast = document.createElement('div');
    toast.className = 'toast-app toast-app-' + type;
    toast.style.cssText =
        'padding:12px 16px;border-radius:8px;color:#fff;font-size:14px;box-shadow:0 8px 24px rgba(0,0,0,.15);' +
        'opacity:0;transform:translateX(20px);transition:all .25s ease;' +
        (type === 'success' ? 'background:#16a34a;' : type === 'error' ? 'background:#dc2626;' : type === 'warn' ? 'background:#d97706;' : 'background:#2563eb;');
    toast.textContent = message;
    container.appendChild(toast);

    requestAnimationFrame(function () {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    });

    setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        setTimeout(function () { toast.remove(); }, 300);
    }, 3500);
}
