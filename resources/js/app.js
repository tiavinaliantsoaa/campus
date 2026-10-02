document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-sidebar-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');

    if (toggle && sidebar && overlay) {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    if (event.target.closest('[data-sidebar-overlay]') && sidebar && overlay) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }

    const dropdownToggle = event.target.closest('[data-dropdown-toggle]');

    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        if (dropdownToggle && dropdown.contains(dropdownToggle)) {
            dropdown.querySelector('[data-dropdown-panel]')?.classList.toggle('hidden');
        } else if (!event.target.closest('[data-dropdown]')) {
            dropdown.querySelector('[data-dropdown-panel]')?.classList.add('hidden');
        }
    });

    const confirmTrigger = event.target.closest('[data-confirm]');

    if (confirmTrigger) {
        event.preventDefault();
        const dialog = document.querySelector('[data-confirm-dialog]');
        const form = dialog?.querySelector('form');
        const message = dialog?.querySelector('[data-confirm-message]');

        if (!dialog || !form || !message) {
            return;
        }

        form.action = confirmTrigger.dataset.action || '';
        const method = dialog.querySelector('[data-confirm-method]');

        if (method) {
            method.value = confirmTrigger.dataset.method || 'POST';
        }

        message.textContent = confirmTrigger.dataset.message || 'Confirmer cette action ?';
        dialog.showModal();
    }
});

document.querySelector('[data-confirm-cancel]')?.addEventListener('click', () => {
    document.querySelector('[data-confirm-dialog]')?.close();
});

document.querySelectorAll('[data-auto-submit]').forEach((form) => {
    form.addEventListener('change', () => {
        if (form instanceof HTMLFormElement) {
            form.requestSubmit();
        }
    });
});
