// ============================================================
// VULA MARKET — App JS (minimal vanilla)
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss flash messages after 5s
    document.querySelectorAll('.flash').forEach(el => {
        setTimeout(() => el.remove(), 5000);
    });

    // Confirm destructive actions
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });

    // Image preview on file input
    const imgInput   = document.getElementById('image');
    const imgPreview = document.getElementById('image-preview');
    if (imgInput && imgPreview) {
        imgInput.addEventListener('change', () => {
            const file = imgInput.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => {
                imgPreview.src           = e.target.result;
                imgPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }

    // Scroll chat to bottom
    const chatBox = document.querySelector('.chat-messages');
    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
});
