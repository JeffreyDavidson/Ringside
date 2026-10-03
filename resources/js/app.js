import './bootstrap';
import { Alpine, Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import AlpineUI from '@alpinejs/ui';
import rosterCombobox from './roster-combobox';
import '../css/app.css';

Alpine.plugin(AlpineUI);
Alpine.data('rosterCombobox', rosterCombobox);

const sidebarExpandedStorageKey = 'ringside.sidebar.expanded';

// Browsers can block storage (privacy settings, sandboxed frames); the sidebar preference is optional.
const readSidebarExpanded = () => {
    try {
        return window.localStorage.getItem(sidebarExpandedStorageKey);
    } catch {
        return null;
    }
};

const rememberSidebarExpanded = expanded => {
    try {
        window.localStorage.setItem(sidebarExpandedStorageKey, String(expanded));
    } catch {
        // The preference only lasts for this page when storage is unavailable.
    }
};

const storedSidebarExpanded = readSidebarExpanded();

Alpine.store('sidebar', {
    expanded: storedSidebarExpanded === null ? true : storedSidebarExpanded === 'true',
    hovered: false,
    mobileOpen: false,
    mobileTrigger: null,
    toggle() {
        this.expanded = !this.expanded;
        rememberSidebarExpanded(this.expanded);
    },
    openMobile(trigger = null) {
        this.mobileTrigger = trigger;
        this.mobileOpen = true;
    },
    closeMobile() {
        const trigger = this.mobileTrigger;

        this.mobileOpen = false;
        this.mobileTrigger = null;

        // The page stops being inert after Alpine's next flush, so return focus once it has.
        if (trigger) {
            window.setTimeout(() => trigger.focus());
        }
    },
});

// The mobile drawer is a modal dialog; close it when the layout switches to the desktop sidebar.
window.matchMedia('(min-width: 1024px)').addEventListener('change', event => {
    if (event.matches) {
        Alpine.store('sidebar').closeMobile();
    }
});

// Livewire starts Alpine and dispatches livewire:init synchronously.
Livewire.start();
