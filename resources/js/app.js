import './bootstrap';
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Alpine from 'alpinejs';
import Clipboard from '@ryangjchandler/alpine-clipboard';
import AlpineUI from '@alpinejs/ui';
import '../../vendor/rappasoft/laravel-livewire-tables/resources/imports/laravel-livewire-tables-all.js';
import '../css/app.css';

import.meta.glob(['../images/**']);

// Make Alpine available globally
window.Alpine = Alpine;

// Register plugins BEFORE defining stores
Alpine.plugin(Clipboard);
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

// Initialize Alpine.js immediately
Alpine.start();
console.log('Alpine.js started');

// Start Livewire after Alpine  
Livewire.start();
