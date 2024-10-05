import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Clipboard from '@ryangjchandler/alpine-clipboard'
import '../../vendor/rappasoft/laravel-livewire-tables/resources/imports/laravel-livewire-tables-all.js';
import ui from '@alpinejs/ui';
import '../vendors/keenicons/styles.bundle.css';
import '../css/modal.css';
import '../css/app.css';

document.addEventListener('alpine:init', () => {
    Alpine.data('darkMode', () => ({
        darkMode: Alpine.$persist(true).as('darkMode'),
        toggleDark() {
            this.darkMode = !this.darkMode;
        },
        init()
        {
            if (this.darkMode === "undefined")
            {
                this.darkMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? true : false;
            }
        }
    }));
});

Alpine.plugin(Clipboard)
Alpine.plugin(ui)
Livewire.start();
