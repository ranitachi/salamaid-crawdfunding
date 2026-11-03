import './bootstrap';

document.querySelectorAll('[data-public-search]').forEach((input) => {
    input.addEventListener('input', (event) => {
        const term = event.target.value.toLowerCase().trim();
        document.querySelectorAll('[data-campaign-card]').forEach((card) => {
            card.hidden = term !== '' && !card.textContent.toLowerCase().includes(term);
        });
    });
});

document.querySelectorAll('input[name="amount_preset"]').forEach((input) => {
    input.addEventListener('change', () => {
        const amount = document.querySelector('#public-amount');
        if (amount) amount.value = input.value;
        const custom = document.querySelector('#public-amount-custom');
        if (custom) custom.value = '';
    });
});

document.querySelector('#public-amount-custom')?.addEventListener('input', (event) => {
    const amount = document.querySelector('#public-amount');
    if (amount && event.target.value) amount.value = event.target.value;
    document.querySelectorAll('input[name="amount_preset"]').forEach((input) => { input.checked = false; });
});

document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = button.parentElement.querySelector('input');
        const icon = button.querySelector('.material-symbols-outlined');
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        icon.textContent = visible ? 'visibility' : 'visibility_off';
        button.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
    });
});
