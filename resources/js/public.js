import './bootstrap';

document.querySelectorAll('[data-public-search]').forEach((input) => {
    input.addEventListener('input', (event) => {
        const term = event.target.value.toLowerCase().trim();
        document.querySelectorAll('[data-campaign-card]').forEach((card) => {
            card.hidden = term !== '' && !card.textContent.toLowerCase().includes(term);
        });
    });
});

document.querySelectorAll('[data-auto-submit-search]').forEach((input) => {
    let timer;
    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            input.form?.requestSubmit();
        }, 350);
    });
});

const loadMoreStateElement = document.querySelector('#public-load-more-state');
if (loadMoreStateElement) {
    const loadMoreState = JSON.parse(loadMoreStateElement.textContent || '{}');
    const loadMoreSections = [
        { key: 'featured', selector: '.public-priority-row' },
        { key: 'programs', selector: '.public-home .public-program-feed' },
        { key: 'campaigns', selector: '.public-program-page .public-program-feed' },
    ];

    loadMoreSections.forEach(({ key, selector }) => {
        const target = document.querySelector(selector);
        let nextUrl = loadMoreState[key];
        if (!target || !nextUrl) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'public-button public-load-more';
        button.textContent = 'Lihat selanjutnya';
        target.after(button);

        button.addEventListener('click', async () => {
            button.disabled = true;
            button.textContent = 'Memuat...';

            try {
                const response = await fetch(nextUrl, { headers: { Accept: 'text/html' } });
                if (!response.ok) throw new Error('Gagal memuat data berikutnya.');

                const html = await response.text();
                const documentFragment = new DOMParser().parseFromString(html, 'text/html');
                const incoming = documentFragment.querySelector(selector);
                const incomingState = documentFragment.querySelector('#public-load-more-state');

                if (!incoming) throw new Error('Data berikutnya tidak tersedia.');
                Array.from(incoming.children).forEach((item) => target.append(item));

                const nextState = incomingState ? JSON.parse(incomingState.textContent || '{}') : {};
                nextUrl = nextState[key] || null;
                if (!nextUrl) {
                    button.remove();
                    return;
                }

                button.disabled = false;
                button.textContent = 'Lihat selanjutnya';
            } catch (error) {
                button.disabled = false;
                button.textContent = 'Coba lagi';
            }
        });
    });
}

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
