/**
 * Searchable, server-backed select for roster records (wrestlers, tag teams, referees).
 *
 * Alpine UI's combobox provides the keyboard handling and ARIA wiring. This component keeps the
 * chosen ids in sync with a Livewire property (deferred, like wire:model) and loads at most
 * `limit` matching options from the server instead of embedding the whole roster in the page.
 *
 * `messages` holds the translated strings the component shows or announces: `searching`, `empty`,
 * `capped`, `one`, `many` (with a `:count` placeholder), `remove` (with `:name`) and `unknown`.
 */
export default function rosterCombobox({ path, kind, multiple, labels, limit, messages }) {
    let root = null;
    let stopWatchingFocus = () => {};

    return {
        selected: multiple ? [] : null,
        options: [],
        labels: Object.fromEntries(labels.map(label => [label.id, label.name])),
        limit,
        term: null,
        loaded: false,
        loading: false,
        ticket: 0,

        init() {
            root = this.$el;
            this.guardAgainstLateRefocus();
            this.selected = this.readLivewireValue();

            this.$watch('selected', value => this.writeLivewireValue(value));
            this.$wire.$watch(path, value => {
                if (JSON.stringify(value ?? this.emptyValue()) !== JSON.stringify(this.selected)) {
                    this.selected = this.readLivewireValue();
                }
            });
        },

        /**
         * Alpine UI refocuses the search box a frame or two after an option is chosen. If focus has
         * already moved to another field by then (the user clicked or tabbed on, or a test typed
         * into the next field), that late refocus steals it back and the next keystrokes land in
         * this box, which in single mode also wipes the chosen name. Programmatic focus is ignored
         * while focus sits outside this combobox, unless the user pressed inside it since then
         * (clicking its label still focuses it).
         */
        guardAgainstLateRefocus() {
            // Alpine UI adds role="combobox" after this component initialises, so select by the rendered attribute.
            const input = root.querySelector('input[data-field]');
            const focus = input.focus.bind(input);
            let focusLeftAt = 0;
            let pressedInsideAt = 0;

            stopWatchingFocus = (() => {
                const noteFocusLeft = event => {
                    if (!root.contains(event.target)) {
                        focusLeftAt = window.performance.now();
                    }
                };

                document.addEventListener('focusin', noteFocusLeft, true);

                return () => document.removeEventListener('focusin', noteFocusLeft, true);
            })();
            root.addEventListener('pointerdown', () => (pressedInsideAt = window.performance.now()), true);

            input.focus = options => {
                const active = document.activeElement;
                const focusIsElsewhere = active !== null && active !== document.body && !root.contains(active);

                if (focusIsElsewhere && focusLeftAt > pressedInsideAt) {
                    return;
                }

                focus(options);
            };
        },

        destroy() {
            stopWatchingFocus();
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

        /**
         * The text Alpine UI writes into the search box. Alpine UI passes the whole selection, so in
         * multiple mode the box stays empty for searching; the chips show what is selected.
         */
        displayValue(value) {
            return multiple ? '' : this.labelFor(value);
        },

        labelFor(id) {
            return this.labels[id] ?? messages.unknown;
        },

        removeLabelFor(id) {
            return messages.remove.replace(':name', this.labelFor(id));
        },

        selectedIds() {
            return multiple ? this.selected : this.selected === null ? [] : [this.selected];
        },

        get status() {
            if (this.loading) {
                return messages.searching;
            }

            if (!this.loaded) {
                return '';
            }

            if (this.options.length === 0) {
                return messages.empty;
            }

            if (this.options.length >= this.limit) {
                return messages.capped;
            }

            return (this.options.length === 1 ? messages.one : messages.many).replace(':count', this.options.length);
        },

        /** Escape with the list closed belongs to the surrounding modal, which Alpine UI would swallow. */
        passEscapeWhenClosed(event, isOpen) {
            if (isOpen) {
                return;
            }

            event.stopPropagation();
            root.parentElement.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        },

        /** The visible options belong to the previous search term until the new results arrive. */
        ignoreEnterWhileLoading(event) {
            if (!this.loading) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
        },

        /** Typing over the chosen name in single mode starts a new search instead of editing the name. */
        startNewTermOverLabel(event) {
            const printable = event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey;

            if (
                multiple ||
                !printable ||
                this.selected === null ||
                event.target.value !== this.labelFor(this.selected)
            ) {
                return;
            }

            event.target.value = '';
        },

        remove(id, index) {
            this.selected = this.selected.filter(selectedId => selectedId !== id);

            this.$nextTick(() => {
                const next = root.querySelectorAll('[data-test="selected-chips"] button')[index];

                if (next) {
                    next.focus();

                    return;
                }

                root.querySelector('[role="combobox"]').focus();
            });
        },

        /** Show the unfiltered list (the first `limit` bookable records) unless it is already showing. */
        showAll() {
            if (this.term !== '') {
                this.search('');
            }
        },

        /** Called on every keystroke, before the debounced search, so stale options cannot be chosen. */
        startSearch() {
            this.ticket++;
            this.options = [];
            this.loading = true;
        },

        async search(term) {
            const ticket = ++this.ticket;
            this.term = term;
            this.loading = true;

            try {
                const results = await this.$wire.searchRoster(kind, term);

                // The component may have been removed or replaced, or a newer search started.
                if (ticket !== this.ticket || !Array.isArray(results)) {
                    return;
                }

                results.forEach(result => {
                    this.labels[result.id] = result.name;
                });
                this.options = results;
                this.loaded = true;
            } finally {
                if (ticket === this.ticket) {
                    this.loading = false;
                }
            }
        },
    };
}
