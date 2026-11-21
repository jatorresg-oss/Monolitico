/**
 * Dashboard / Inicio - Panel principal con estadísticas
 */

import { 
    getSprints, 
    getRetrospectives, 
    getFeedbackByRetrospective,
    checkAllServices,
    formatDate,
    showToast 
} from './api.js';

let currentSprints = [];
let currentRetrospectives = [];

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
        <div class="service-badge ${status.retrospectives ? 'online' : 'offline'}">
            <i class="fas fa-comments"></i> Retrospective Service ${status.retrospectives ? '●' : '○'}
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
    try {
        showLoading(true);
        
        const [sprints, retrospectives] = await Promise.all([
            getSprints(),
            getRetrospectives()
        ]);

        currentSprints = sprints || [];
        currentRetrospectives = retrospectives || [];

        renderStats();
        renderRecentSprints();
        
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showToast('Error al cargar el dashboard', 'error');
    } finally {
        showLoading(false);
    }
}

function renderStats() {
    const container = document.getElementById('stats-container');
    if (!container) return;

    const completedSprints = currentSprints.filter(s => s.status === 'completed').length;
    const totalRetrospectives = currentRetrospectives.length;
    
    // Count actions from all retrospectives
    let totalActions = 0;
    let completedActions = 0;
    
    // This would need to fetch feedback for each retrospective
    // For now, show placeholder

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
                <h3>Retrospectivas</h3>
                <div class="stat-value">${totalRetrospectives}</div>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-icon actions"><i class="fas fa-tasks"></i></div>
            <div class="stat-info">
                <h3>Sprints Completados</h3>
                <div class="stat-value">${completedSprints}</div>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-icon completion"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3>Tasa Completitud</h3>
                <div class="stat-value">${currentSprints.length > 0 ? Math.round((completedSprints / currentSprints.length) * 100) : 0}%</div>
            </div>
        </article>
    `;
}

async function renderRecentSprints() {
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

    container.innerHTML = recentSprints.map(sprint => `
        <article class="sprint-card" data-sprint-id="${sprint.id}">
            <div class="sprint-card-header">
                <h3>${escapeHtml(sprint.name)}</h3>
                <div class="sprint-card-actions">
                    <a href="retrospectiva.html?sprint_id=${sprint.id}" class="btn btn-sm btn-add">
                        <i class="fas fa-comments"></i> Retrospectiva
                    </a>
                    <a href="sprints.html?id=${sprint.id}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
            </div>
            <div class="sprint-card-dates">
                <span><i class="fas fa-play"></i> ${formatDate(sprint.start_date)}</span>
                <span><i class="fas fa-stop"></i> ${formatDate(sprint.end_date)}</span>
            </div>
            <div class="sprint-card-stats">
                <span class="stat-item"><i class="fas fa-circle" style="color: ${getStatusColor(sprint.status)}"></i> ${getStatusLabel(sprint.status)}</span>
            </div>
        </article>
    `).join('');
}

function getStatusColor(status) {
    const colors = {
        'planned': 'var(--info)',
        'active': 'var(--primary)',
        'completed': 'var(--success)',
        'cancelled': 'var(--danger)'
    };
    return colors[status] || 'var(--gray-500)';
}

function getStatusLabel(status) {
    const labels = {
        'planned': 'Planificado',
        'active': 'Activo',
        'completed': 'Completado',
        'cancelled': 'Cancelado'
    };
    return labels[status] || status;
}

function showLoading(show) {
    const main = document.getElementById('main-content');
    if (main) {
        if (show) main.classList.add('loading');
        else main.classList.remove('loading');
    }
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