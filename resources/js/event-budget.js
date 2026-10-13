export default () => ({
    online: window.navigator.onLine,
    requestFailed: false,
    dirty: false,
    saved: false,
    unsubscribe: null,
    onOnline: null,
    onOffline: null,
    onLeave: null,
    onNavigate: null,
    onDraft: null,
    onSaved: null,
    get blocked() {
        return !this.online || this.requestFailed;
    },
    init() {
        this.onOnline = () => {
            this.online = true;
        };
        this.onOffline = () => {
            this.online = false;
        };
        this.onLeave = event => {
            if (!this.dirty) {
                return;
            }
            event.preventDefault();
            event.returnValue = '';
        };
        this.onNavigate = event => {
            if (this.dirty && !window.confirm(this.$el.dataset.leave)) {
                event.preventDefault();
            }
        };
        this.onDraft = () => {
            this.dirty = false;
            this.$nextTick(() => {
                const form = this.$el.querySelector('[data-budget-draft]');
                form?.scrollIntoView({ block: 'start' });
                form?.querySelector('input,select')?.focus({ preventScroll: true });
            });
        };
        this.onSaved = () => {
            this.dirty = false;
        };
        window.addEventListener('online', this.onOnline);
        window.addEventListener('offline', this.onOffline);
        window.addEventListener('beforeunload', this.onLeave);
        document.addEventListener('livewire:navigate', this.onNavigate);
        this.$el.addEventListener('budget-draft-opened', this.onDraft);
        this.$el.addEventListener('budget-draft-closed', this.onSaved);
        this.unsubscribe = this.$wire.interceptMessage(({ message, cancel, onSuccess, onFailure, onError }) => {
            const actions = [...message.actions];
            const refreshOnly = actions.length > 0 && actions.every(action => action.name === 'refreshState');
            if ((!this.online || this.requestFailed) && !refreshOnly) {
                cancel();
                return;
            }
            const startsDraft = [...message.actions].some(action =>
                ['beginLine', 'beginMovement', 'beginArchive'].includes(action.name),
            );
            if (this.dirty && startsDraft && !window.confirm(this.$el.dataset.leave)) {
                cancel();
                return;
            }
            onSuccess(({ onMorphed }) =>
                onMorphed(() => {
                    if (refreshOnly) {
                        this.requestFailed = false;
                    }
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
    destroy() {
        this.unsubscribe?.();
        window.removeEventListener('online', this.onOnline);
        window.removeEventListener('offline', this.onOffline);
        window.removeEventListener('beforeunload', this.onLeave);
        document.removeEventListener('livewire:navigate', this.onNavigate);
        this.$el.removeEventListener('budget-draft-opened', this.onDraft);
        this.$el.removeEventListener('budget-draft-closed', this.onSaved);
    },
});
