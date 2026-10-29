/**
 * CerriFlash - Funciones Globales Frontend y Helpers JavaScript
 * “Tu pedido, rápido y seguro.”
 */

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar tooltips de Bootstrap si existen
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltipTriggerList.length > 0) {
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    // Auto-cerrar alertas flash tras 5 segundos
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 6000);
});

/**
 * Sistema de Sonido con Web Audio API para CerriFlash (Nuevo pedido / alerta)
 */
const CerriSound = {
    playNotification() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            // Tono alegre de dos beeps rápidos (tipo app de delivery)
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.12); // A5

            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start();
            osc.stop(ctx.currentTime + 0.36);
        } catch (e) {
            console.warn('AudioContext no disponible o bloqueado por el navegador.', e);
        }
    },

    playAlert() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'triangle';
            osc.frequency.setValueAtTime(440, ctx.currentTime);
            osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.15);

            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start();
            osc.stop(ctx.currentTime + 0.41);
        } catch (e) {
            console.warn('AudioContext error:', e);
        }
    }
};

/**
 * Helper para peticiones AJAX con JSON
 */
async function cfFetch(url, options = {}) {
    const defaultHeaders = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };

    options.headers = {
        ...defaultHeaders,
        ...(options.headers || {})
    };

    try {
        const response = await fetch(url, options);
        const data = await response.json();
        return { ok: response.ok, status: response.status, data };
    } catch (error) {
        console.error('Error en cfFetch:', error);
        return { ok: false, status: 500, error: error.message };
    }
}

