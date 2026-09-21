import '../css/app.css';

for (const button of document.querySelectorAll('[data-password-toggle]')) {
    const input = document.getElementById(button.getAttribute('aria-controls'));

    if (!(input instanceof window.HTMLInputElement)) {
        continue;
    }

    button.hidden = false;
    button.addEventListener('click', () => {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', visible ? button.dataset.hideLabel : button.dataset.showLabel);
        button.querySelector('[data-password-show]').hidden = visible;
        button.querySelector('[data-password-hide]').hidden = !visible;
    });
}

document.querySelector('[data-auth-error], [aria-invalid="true"]')?.focus();
