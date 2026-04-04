import './bootstrap';
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import AlpineUI from '@alpinejs/ui';
import '../css/app.css';

import.meta.glob(['../media/**']);

// Start Livewire (which includes Alpine.js)
Livewire.start();

// Access Alpine.js through Livewire's global instance
document.addEventListener('livewire:init', () => {
    // Register Alpine UI plugin
    Alpine.plugin(AlpineUI);

    // Global sidebar state store
    Alpine.store('sidebar', {
        expanded: true,
        hovered: false,
        mobileOpen: false,
        toggle() {
            this.expanded = !this.expanded;
        },
        openMobile() {
            this.mobileOpen = true;
        },
        closeMobile() {
            this.mobileOpen = false;
        },
    });

    // Debug logging
    console.log('Alpine.js version:', Alpine.version);
    console.log('Sidebar store defined:', Alpine.store('sidebar'));
});
