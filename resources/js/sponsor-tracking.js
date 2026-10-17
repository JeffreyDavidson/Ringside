export default () => ({
    requestFailed: false,
    online: window.navigator.onLine,
    unsubscribe: null,
    onOnline: null,
    onOffline: null,
    onDraft: null,
    init() {
        this.onOnline = () => {
            this.online = true;
        };
        this.onOffline = () => {
            this.online = false;
        };
        this.onDraft = () =>
            this.$nextTick(() => {
                const draft = this.$el.querySelector('[data-sponsor-draft]');
                draft?.scrollIntoView({ block: 'start' });
                draft?.querySelector('input, select')?.focus({ preventScroll: true });
            });
        window.addEventListener('online', this.onOnline);
        window.addEventListener('offline', this.onOffline);
        this.$el.addEventListener('sponsor-draft-opened', this.onDraft);
        this.unsubscribe = this.$wire.interceptMessage(({ cancel, onFailure, onError }) => {
            if (this.requestFailed || !this.online) {
                cancel();
                return;
            }
            onFailure(() => {
                this.requestFailed = true;
            });
            onError(({ preventDefault }) => {
                preventDefault();
                this.requestFailed = true;
            });
        });
    },
    destroy() {
        this.unsubscribe?.();
        window.removeEventListener('online', this.onOnline);
        window.removeEventListener('offline', this.onOffline);
        this.$el.removeEventListener('sponsor-draft-opened', this.onDraft);
    },
});
