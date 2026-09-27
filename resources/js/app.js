import './bootstrap';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import AlpineUI from '@alpinejs/ui';
import '../css/app.css';

import.meta.glob(['../media/**']);

Alpine.plugin(AlpineUI);

const sidebarExpandedStorageKey = 'ringside.sidebar.expanded';
const storedSidebarExpanded = window.localStorage.getItem(sidebarExpandedStorageKey);

Alpine.store('sidebar', {
    expanded: storedSidebarExpanded === null ? true : storedSidebarExpanded === 'true',
    hovered: false,
    mobileOpen: false,
    toggle() {
        this.expanded = !this.expanded;
        window.localStorage.setItem(sidebarExpandedStorageKey, String(this.expanded));
    },
    openMobile() {
        this.mobileOpen = true;
    },
    closeMobile() {
        this.mobileOpen = false;
    },
});

// Livewire starts Alpine and dispatches livewire:init synchronously.
Livewire.start();
