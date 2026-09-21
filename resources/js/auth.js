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

for (const button of document.querySelectorAll('[data-submitting-label]')) {
    const form = button.form;
    const label = button.textContent;
    const resendStatus = form.querySelector('#resend-status');
    const resendAt = Date.now() + Number(button.dataset.resendSeconds || 0) * 1000;
    let submitting = false;

    const updateCooldown = () => {
        const seconds = Math.max(0, Math.ceil((resendAt - Date.now()) / 1000));
        button.disabled = submitting || seconds > 0;

        if (resendStatus) {
            resendStatus.textContent =
                seconds > 0
                    ? resendStatus.dataset.waitLabel.replace(':seconds', seconds)
                    : resendStatus.dataset.readyLabel;
        }

        return seconds;
    };

    form.addEventListener('submit', event => {
        if (event.defaultPrevented) {
            return;
        }

        if (submitting || updateCooldown() > 0) {
            event.preventDefault();
            return;
        }

        submitting = true;
        button.disabled = true;
        button.textContent = button.dataset.submittingLabel;
        form.setAttribute('aria-busy', 'true');
    });

    window.addEventListener('pageshow', () => {
        submitting = false;
        button.textContent = label;
        form.removeAttribute('aria-busy');
        updateCooldown();
    });

    if (updateCooldown() > 0) {
        const interval = window.setInterval(() => {
            if (updateCooldown() === 0) {
                window.clearInterval(interval);
            }
        }, 1000);
    }
}
