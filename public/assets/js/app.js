// Pequeñas mejoras de UI compartidas
(function () {
    document.addEventListener('submit', function (e) {
        const btn = e.target.querySelector('button[type="submit"]');
        if (btn && !btn.disabled) {
            btn.dataset.originalText = btn.innerText;
            btn.disabled = true;
            btn.style.opacity = '0.7';
            setTimeout(() => {
                if (btn.disabled) {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            }, 8000);
        }
    });
})();
