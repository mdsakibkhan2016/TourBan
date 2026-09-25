/* Toast notifications — showToast(message, type)
   types: success | error | warn | info
   Styled by assets/css/polish.css */
function showToast(message, type) {
    type = type || 'info';
    var container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        document.body.appendChild(container);
    }

    var icons = { success: '✓', error: '✕', warn: '!', info: 'i' };

    var toast = document.createElement('div');
    toast.className = 'toast-app toast-app-' + type;
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

    var icon = document.createElement('span');
    icon.className = 'toast-app-icon';
    icon.textContent = icons[type] || icons.info;

    var text = document.createElement('span');
    text.className = 'toast-app-msg';
    text.textContent = message;

    var close = document.createElement('button');
    close.type = 'button';
    close.className = 'toast-app-close';
    close.setAttribute('aria-label', 'Close notification');
    close.innerHTML = '&times;';

    var bar = document.createElement('span');
    bar.className = 'toast-app-bar';

    toast.appendChild(icon);
    toast.appendChild(text);
    toast.appendChild(close);
    toast.appendChild(bar);
    container.appendChild(toast);

    var removed = false;
    function dismiss() {
        if (removed) return;
        removed = true;
        toast.classList.add('toast-app-leaving');
        setTimeout(function () { toast.remove(); }, 260);
    }

    close.addEventListener('click', dismiss);
    setTimeout(dismiss, 3600);
}
