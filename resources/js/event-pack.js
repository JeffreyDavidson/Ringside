const form = document.querySelector('[data-pack-form]');

if (form) {
    let dirty = Boolean(document.querySelector('.pack-error'));
    let nextSegment = Date.now();
    const markDirty = () => {
        dirty = true;
        const status = form.querySelector('[data-save-state]');
        if (status) {
            status.textContent = form.dataset.dirtyMessage;
        }
    };
    window.addEventListener('production-pack-saved', event => {
        const version = form.querySelector('[name=version]');
        if (Number(version.value) !== event.detail.previousVersion) {
            return;
        }
        version.value = String(event.detail.version);
        form.querySelector('[name=fingerprint]').value = event.detail.fingerprint;
    });
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', () => {
        dirty = false;
    });
    window.addEventListener('beforeunload', event => {
        if (!dirty) {
            return;
        }
        event.preventDefault();
        event.returnValue = '';
    });
    form.querySelector('[data-add-segment]')?.addEventListener('click', () => {
        const template = form.querySelector('[data-segment-template]');
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace('__INDEX__', String(nextSegment));
        });
        nextSegment += 1;
        fragment.querySelector('[data-segment-id]').value = window.crypto.randomUUID();
        const firstInput = fragment.querySelector('input:not([type=hidden])');
        form.querySelector('[data-segments]').append(fragment);
        firstInput.focus();
        markDirty();
    });
    form.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-segment]');
        if (!button) {
            return;
        }
        button.closest('[data-segment]').remove();
        form.querySelector('[data-add-segment]').focus();
        markDirty();
    });
    document.querySelector('[data-print-saved]')?.addEventListener('click', event => {
        if (!dirty) {
            return;
        }
        if (!window.confirm(event.currentTarget.dataset.discardMessage)) {
            event.preventDefault();
            return;
        }
        dirty = false;
    });
}
