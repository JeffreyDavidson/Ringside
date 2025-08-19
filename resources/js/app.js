import './bootstrap';
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import AlpineUI from '@alpinejs/ui';
import '../css/app.css';
import '../css/keenicons.css';

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
        toggle() {
            this.expanded = !this.expanded;
        },
    });

    // Debug logging
    console.log('Alpine.js version:', Alpine.version);
    console.log('Sidebar store defined:', Alpine.store('sidebar'));
});
