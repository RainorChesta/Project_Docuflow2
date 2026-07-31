

import Alpine from 'alpinejs';
import './jodit';
import { initJoditEditor } from './jodit';

document.addEventListener('DOMContentLoaded', () => {
    initJoditEditor('#jodit-editor');
    initJoditEditor('#editor-shared');
});

window.Alpine = Alpine;

Alpine.start();
