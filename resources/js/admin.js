import './bootstrap';

const sidebar = document.querySelector('[data-admin-sidebar]');
const menuToggle = document.querySelector('[data-admin-menu-toggle]');

menuToggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('is-open');
});

document.addEventListener('click', (event) => {
    const closeTarget = event.target.closest('[data-admin-close-sidebar]');

    if (closeTarget) {
        sidebar?.classList.remove('is-open');
    }
});

document.querySelectorAll('[data-admin-modal-open]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const modal = document.getElementById(trigger.dataset.adminModalOpen);

        if (modal) {
            modal.hidden = false;
        }
    });
});

document.querySelectorAll('[data-admin-modal-close]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        const modal = trigger.closest('[data-admin-modal]');

        if (modal) {
            modal.hidden = true;
        }
    });
});

document.querySelectorAll('[data-admin-modal]').forEach((modal) => {
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.hidden = true;
        }
    });
});
