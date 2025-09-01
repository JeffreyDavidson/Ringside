import './bootstrap';
import { Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import '../css/app.css';
import '../css/keenicons.css';

// Set up Alpine globally without any plugins
window.Alpine = Alpine;

// Start Alpine directly without Livewire or any plugins
Alpine.start();

console.log('Minimal Alpine.js loaded for auth pages (without Livewire)');
