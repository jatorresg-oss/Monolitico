/**
 * Dashboard / Inicio - Panel principal con estadísticas.
 */

import {
    getSprints,
    getAllItems,
    getFeedbackStats,
    checkAllServices,
    formatDate,
    escapeHtml,
    showToast
} from './api.js';

let currentSprints = [];
let allItems = [];

document.addEventListener('DOMContentLoaded', async () => {
    await checkServicesStatus();
    await loadDashboard();
});

async function checkServicesStatus() {
    const container = document.getElementById('services-status');
    if (!container) return;

    const status = await checkAllServices();

    container.innerHTML = `
        <div class="service-badge ${status.sprints ? 'online' : 'offline'}">
            <i class="fas fa-calendar-alt"></i> Sprint Service ${status.sprints ? '●' : '○'}
        </div>
        <div class="service-badge ${status.retrospective ? 'online' : 'offline'}">
            <i class="fas fa-comments"></i> Retrospective Service ${status.retrospective ? '●' : '○'}
        </div>
        <div class="service-badge ${status.feedback ? 'online' : 'offline'}">
            <i class="fas fa-list"></i> Feedback Service ${status.feedback ? '●' : '○'}
        </div>
        <div class="service-badge ${status.actionTracking ? 'online' : 'offline'}">
            <i class="fas fa-tasks"></i> Action Tracking ${status.actionTracking ? '●' : '○'}
        </div>
    `;
}

async function loadDashboard() {
    showLoading(true);
    try {
        const [sprints, items, stats] = await Promise.all([
            getSprints(),
            getAllItems(),
            getFeedbackStats()
        ]);

        currentSprints = sprints || [];
        allItems = items || [];

        renderStats(stats);
        renderRecentSprints();
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showToast('Error al cargar el dashboard', 'error');
    } finally {
        showLoading(false);
    }
}

function renderStats(stats) {
    const container = document.getElementById('stats-container');
    if (!container) return;

    const acciones = stats?.acciones || { total: 0, cumplidas: 0, pendientes: 0, tasa_cumplimiento: 0 };
    const totalItems = stats?.total_items ?? allItems.length;

    container.innerHTML = `
        <article class="stat-card">
            <div class="stat-icon sprints"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-info">
                <h3>Total Sprints</h3>
                <div class="stat-value">${currentSprints.length}</div>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-icon retrospectives"><i class="fas fa-comments"></i></div>
            <div class="stat-info">
                <h3>Items Registrados</h3>
                <div class="stat-value">${totalItems}</div>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-icon actions"><i class="fas fa-tasks"></i></div>
            <div class="stat-info">
                <h3>Acciones Pendientes</h3>
                <div class="stat-value">${acciones.pendientes}</div>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-icon completion"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3>Tasa de Cumplimiento</h3>
                <div class="stat-value">${acciones.tasa_cumplimiento ?? 0}%</div>
            </div>
        </article>
    `;
}

function renderRecentSprints() {
    const container = document.getElementById('recent-sprints');
    if (!container) return;

    const recentSprints = currentSprints.slice(0, 5);

    if (recentSprints.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-plus"></i>
                <h3>No hay sprints registrados</h3>
                <p>Crea tu primer sprint para empezar</p>
                <a href="sprints.html" class="btn btn-primary"><i class="fas fa-plus"></i> Crear sprint</a>
            </div>
        `;
        return;
    }

    container.innerHTML = recentSprints.map(sprint => {
        const items = allItems.filter(item => Number(item.sprint_id) === Number(sprint.id));
        const acciones = items.filter(item => item.categoria === 'accion');
        const pendientes = acciones.filter(item => !item.cumplida).length;

        return `
            <article class="sprint-card" data-sprint-id="${sprint.id}">
                <div class="sprint-card-header">
                    <h3>${escapeHtml(sprint.nombre)}</h3>
                    <div class="sprint-card-actions">
                        <a href="retrospectiva.html?sprint_id=${sprint.id}" class="btn btn-sm btn-add">
                            <i class="fas fa-comments"></i> Retrospectiva
                        </a>
                    </div>
                </div>
                <div class="sprint-card-dates">
                    <span><i class="fas fa-play"></i> ${formatDate(sprint.fecha_inicio)}</span>
                    <span><i class="fas fa-stop"></i> ${formatDate(sprint.fecha_fin)}</span>
                </div>
                <div class="sprint-card-stats">
                    <span class="stat-item"><i class="fas fa-layer-group"></i> ${items.length} items</span>
                    <span class="stat-item"><i class="fas fa-tasks"></i> ${acciones.length} acciones (${pendientes} pendientes)</span>
                </div>
            </article>
        `;
    }).join('');
}

function showLoading(show) {
    const main = document.getElementById('main-content');
    if (main) main.classList.toggle('loading', show);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'polite');
    toast.innerHTML = `
        <i class="fas ${icons[type] || icons.info} toast-icon" aria-hidden="true"></i>
        <span class="toast-message">${escapeHtml(message)}</span>
        <button class="toast-close" aria-label="Cerrar notificación">&times;</button>
    `;

    toast.querySelector('.toast-close').addEventListener('click', () => toast.remove());
    container.appendChild(toast);

    setTimeout(() => { if (toast.parentNode) toast.remove(); }, 4000);
}

window.showToast = showToast;