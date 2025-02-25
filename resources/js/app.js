import './bootstrap';
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Clipboard from '@ryangjchandler/alpine-clipboard';
import AlpineUI from '@alpinejs/ui';
import '../../vendor/rappasoft/laravel-livewire-tables/resources/imports/laravel-livewire-tables-all.js';
import '../vendors/keenicons/styles.bundle.css';
import '../css/app.css';

import.meta.glob([
    '../images/**',
])

Alpine.plugin(Clipboard);
Alpine.plugin(AlpineUI);
Livewire.start();
