// ===============================================
// ✅ Laravel + Vite main entry for JS
// ===============================================

import './bootstrap';
import Alpine from 'alpinejs';

// Optional: make Alpine globally available
window.Alpine = Alpine;
Alpine.start();

// ===============================================
// ✅ Chart.js setup (used in dashboard)
// ===============================================
import Chart from 'chart.js/auto';
window.Chart = Chart;

// ===============================================
// ✅ Service Worker registration (for PWA support)
// ===============================================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/service-worker.js')
            .then(() => console.log('✅ Service Worker registered successfully'))
            .catch(err => console.error('❌ Service Worker registration failed:', err));
    });
}

// ===============================================
// ✅ Optional: load any static assets via Vite
// ===============================================
import.meta.glob([
    '../images/**',
]);

console.log('✅ app.js loaded and running');
