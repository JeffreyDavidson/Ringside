export default () => ({
    requestFailed: false,
    online: window.navigator.onLine,
    elapsed: 0,
    interval: null,
    unsubscribe: null,
    onOnline: null,
    onOffline: null,
    serverOffset: 0,
    draftListener: null,
    init() {
        this.syncClock();
        this.draftListener = () =>
            this.$nextTick(() => {
                const form = this.$el.querySelector('[data-live-draft]');
                form?.scrollIntoView({ block: 'start' });
                form?.querySelector('input')?.focus({ preventScroll: true });
            });
        this.$el.addEventListener('live-draft-opened', this.draftListener);
        this.interval = window.setInterval(() => this.tick(), 1000);
        this.onOnline = () => {
            this.online = true;
        };
        this.onOffline = () => {
            this.online = false;
        };
        window.addEventListener('online', this.onOnline);
        window.addEventListener('offline', this.onOffline);
        this.unsubscribe = this.$wire.interceptMessage(({ message, cancel, onSuccess, onFailure, onError }) => {
            const refreshOnly = [...message.actions].every(action => action.name === 'refreshState');
            if ((this.requestFailed || !this.online) && !refreshOnly) {
                cancel();
                return;
            }
            onSuccess(({ onMorphed }) =>
                onMorphed(() => {
                    this.requestFailed = false;
                    this.syncClock();
                }),
            );
            onFailure(() => {
                this.requestFailed = true;
            });
            onError(({ preventDefault }) => {
                preventDefault();
                this.requestFailed = true;
            });
        });
    },
    syncClock() {
        this.serverOffset = Date.parse(this.$el.dataset.serverNow) - Date.now();
        this.tick();
    },
    tick() {
        const start = this.$el.dataset.currentStart;
        this.elapsed = start ? Math.max(0, Math.floor((Date.now() + this.serverOffset - Date.parse(start)) / 1000)) : 0;
    },
    destroy() {
        window.clearInterval(this.interval);
        this.$el.removeEventListener('live-draft-opened', this.draftListener);
        this.unsubscribe?.();
        window.removeEventListener('online', this.onOnline);
        window.removeEventListener('offline', this.onOffline);
    },
});
