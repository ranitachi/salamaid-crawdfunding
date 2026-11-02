import './bootstrap';

document.querySelectorAll('[data-public-search]').forEach((input) => {
    input.addEventListener('input', (event) => {
        const term = event.target.value.toLowerCase().trim();
        document.querySelectorAll('[data-campaign-card]').forEach((card) => {
            card.hidden = term !== '' && !card.textContent.toLowerCase().includes(term);
        });
    });
});
