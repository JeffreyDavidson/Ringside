import '../css/event-pack-print.css';

const printButton = document.querySelector('[data-print]');
const selectors = [...document.querySelectorAll('[data-print-section]')];
selectors.forEach(checkbox => {
    checkbox.addEventListener('change', () => {
        document.querySelector(`[data-section="${checkbox.dataset.printSection}"]`).hidden = !checkbox.checked;
        printButton.disabled = !selectors.some(input => input.checked);
    });
});
printButton.addEventListener('click', () => window.print());
