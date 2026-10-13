export default () => ({
    snapshot: null,
    error: null,
    busy: false,
    checked: null,
    ticket: 0,
    messages: {},

    init() {
        this.messages = { ...this.$el.dataset };
        this.$wire.$watch('form.competitors', () => this.invalidate());
        this.$wire.$watch('form.matchType', () => this.invalidate());
    },

    selection() {
        return JSON.stringify([this.$wire.form.matchType, this.$wire.form.competitors]);
    },

    invalidate() {
        if (this.checked === this.selection()) {
            return;
        }

        this.ticket++;
        this.snapshot = null;
        this.error = null;
        this.busy = false;
        this.checked = null;
    },

    async check(page = 1) {
        const ticket = ++this.ticket;
        const selection = this.selection();
        this.checked = selection;
        this.busy = true;
        this.error = null;

        try {
            const snapshot = await this.$wire.checkHistory(page);

            if (ticket !== this.ticket || selection !== this.selection()) {
                return;
            }

            if (snapshot.error) {
                this.snapshot = null;
                this.error = snapshot.error;
                return;
            }

            this.snapshot = snapshot;
        } catch {
            if (ticket === this.ticket) {
                this.snapshot = null;
                this.error = this.messages.failureMessage;
            }
        } finally {
            if (ticket === this.ticket) {
                this.busy = false;
            }
        }
    },

    result(row) {
        if (!row.recorded) {
            return row.finish === this.messages.missingResult ? row.finish : this.messages.incompleteResult;
        }

        if (row.winnerSide === null) {
            return row.finish;
        }

        const winners = row.sides
            .filter(entry => entry.side === row.winnerSide)
            .map(entry => entry.name)
            .join(' & ');
        return `${winners} ${this.messages.byLabel} ${row.finish}`;
    },

    sides(row) {
        const sides = new Map();

        for (const entry of row.sides) {
            const names = sides.get(entry.side) ?? [];
            names.push(entry.deleted ? `${entry.name} (${this.messages.deletedLabel})` : entry.name);
            sides.set(entry.side, names);
        }

        return [...sides.entries()]
            .sort(([first], [second]) => first - second)
            .map(([, names]) => names.join(' & '))
            .join(' vs ');
    },
});
