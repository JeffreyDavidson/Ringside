// Connect the rendered bout edges so long names and responsive row heights keep their actual alignment.
export default () => ({
    observer: null,
    mutations: null,
    signature: '',
    init() {
        this.$nextTick(() => {
            this.observer = new window.ResizeObserver(() => this.draw());
            this.observer.observe(this.$el);
            this.$el.querySelectorAll('[data-bracket-node]').forEach(node => this.observer.observe(node));
            this.mutations = new window.MutationObserver(() => this.draw());
            this.mutations.observe(this.$el, { childList: true, subtree: true, characterData: true });
            this.draw();
        });
    },
    draw() {
        const bounds = this.$el.getBoundingClientRect();
        const svg = this.$el.querySelector('[data-bracket-lines]');
        if (!svg) {
            return;
        }
        const paths = [...this.$el.querySelectorAll('[data-bracket-node]')].flatMap(node => {
            const parent = this.$el.querySelector(`[data-bracket-node="${node.dataset.bracketParent}"]`);
            if (!parent) {
                return [];
            }
            const from = node.getBoundingClientRect();
            const to = parent.getBoundingClientRect();
            const startX = from.right - bounds.left;
            const startY = from.top + from.height / 2 - bounds.top;
            const endX = to.left - bounds.left;
            const endY = to.top + to.height / 2 - bounds.top;
            const middleX = (startX + endX) / 2;
            return [{ id: node.dataset.bracketNode, d: `M ${startX} ${startY} H ${middleX} V ${endY} H ${endX}` }];
        });
        const signature = JSON.stringify(paths);
        if (signature === this.signature) {
            return;
        }
        this.signature = signature;
        svg.replaceChildren(
            ...paths.map(({ id, d }) => {
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', d);
                path.setAttribute('stroke', 'currentColor');
                path.setAttribute('stroke-width', '1');
                path.dataset.bracketConnector = id;
                return path;
            }),
        );
    },
    destroy() {
        this.observer?.disconnect();
        this.mutations?.disconnect();
    },
});
