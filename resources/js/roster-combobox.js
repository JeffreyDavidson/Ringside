/**
 * Searchable, server-backed select for roster records (wrestlers, tag teams, referees).
 *
 * Alpine UI's combobox provides the keyboard handling and ARIA wiring. This component keeps the
 * chosen ids in sync with a Livewire property (deferred, like wire:model) and loads at most a
 * handful of matching options from the server instead of embedding the whole roster in the page.
 */
export default function rosterCombobox({ path, kind, multiple, labels }) {
    return {
        selected: multiple ? [] : null,
        options: [],
        labels: Object.fromEntries(labels.map(label => [label.id, label.name])),
        loaded: false,
        loading: false,
        ticket: 0,

        init() {
            this.selected = this.readLivewireValue();

            this.$watch('selected', value => this.writeLivewireValue(value));
            this.$wire.$watch(path, value => {
                if (JSON.stringify(value ?? this.emptyValue()) !== JSON.stringify(this.selected)) {
                    this.selected = this.readLivewireValue();
                }
            });
        },

        emptyValue() {
            return multiple ? [] : null;
        },

        readLivewireValue() {
            const value = this.$wire.$get(path);

            if (multiple) {
                return Array.isArray(value) ? [...value] : [];
            }

            return value ?? null;
        },

        writeLivewireValue(value) {
            const current = this.$wire.$get(path);

            if (JSON.stringify(current ?? this.emptyValue()) === JSON.stringify(value)) {
                return;
            }

            this.$wire.$set(path, multiple ? [...value] : value, false);
        },

        labelFor(id) {
            return this.labels[id] ?? `#${id}`;
        },

        selectedIds() {
            return multiple ? this.selected : this.selected === null ? [] : [this.selected];
        },

        remove(id) {
            this.selected = this.selected.filter(selectedId => selectedId !== id);
        },

        loadInitial() {
            if (!this.loaded) {
                this.search('');
            }
        },

        async search(term) {
            const ticket = ++this.ticket;
            this.loading = true;

            const results = await this.$wire.searchRoster(kind, term);

            // The component may have been removed or replaced while the request was in flight.
            if (ticket !== this.ticket || !Array.isArray(results)) {
                return;
            }

            results.forEach(result => {
                this.labels[result.id] = result.name;
            });
            this.options = results;
            this.loaded = true;
            this.loading = false;
        },
    };
}
