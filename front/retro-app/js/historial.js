/**
 * Historial Page - Historial de retrospectivas por sprint.
 */

import {
    getSprints,
    getAllItems,
    checkAllServices,
    formatDate,
    formatDateTime,
    isCompleted,
    escapeHtml,
    showToast
} from './api.js';

let currentSprints = [];
let allItems = [];
let filterSprintId = null;

const CATEGORY_ORDER = ['logro', 'impedimento', 'accion', 'comentario', 'otro'];
const CATEGORY_LABELS = {
    logro: 'Logros',
    impedimento: 'Impedimentos',
    accion: 'Acciones',
    comentario: 'Comentarios',
    otro: 'Otros'
};
const CATEGORY_ICONS = {
    logro: 'trophy',
    impedimento: 'exclamation-triangle',
    accion: 'tasks',
    comentario: 'sticky-note',
    otro: 'circle'
};

document.addEventListener('DOMContentLoaded', async () => {
    await checkServicesStatus();
    await loadData();
    setupFilter();

    // Check for sprint_id in URL
    const urlParams = new URLSearchParams(window.location.search);
    const sprintId = urlParams.get('sprint_id');
    if (sprintId) {
        document.getElementById('filter-sprint').value = sprintId;
        filterSprintId = parseInt(sprintId);
        renderHistory();
    }
});

async function checkServicesStatus() {
    const status = await checkAllServices();
    const container = document.getElementById('services-status');
    if (!container) return;

    container.innerHTML = `
        <div class="service-badge ${status.sprints ? 'online' : 'offline'}">
            <i class="fas fa-calendar-alt"></i> Sprint ${status.sprints ? '●' : '○'}
        </div>
        <div class="service-badge ${status.retrospective ? 'online' : 'offline'}">
            <i class="fas fa-comments"></i> Retro ${status.retrospective ? '●' : '○'}
        </div>
        <div class="service-badge ${status.feedback ? 'online' : 'offline'}">
            <i class="fas fa-list"></i> Feedback ${status.feedback ? '●' : '○'}
        </div>
    `;
}

async function loadData() {
    showLoading(true);
    try {
        const [sprints, items] = await Promise.all([getSprints(), getAllItems()]);
        currentSprints = sprints || [];
        allItems = items || [];
        renderSprintFilter();
        renderHistory();
    } catch (error) {
        console.error('Error loading history:', error);
        showToast(error.message || 'Error al cargar el historial', 'error');
    } finally {
        showLoading(false);
    }
}

function renderSprintFilter() {
    const selector = document.getElementById('filter-sprint');
    if (!selector) return;

    const selected = selector.value;
    selector.innerHTML = '<option value="">-- Todos los sprints --</option>' +
        currentSprints.map(sprint =>
            `<option value="${sprint.id}">${escapeHtml(sprint.nombre)} (${formatDate(sprint.fecha_inicio)} - ${formatDate(sprint.fecha_fin)})</option>`
        ).join('');

    if (selected) selector.value = selected;
}

function setupFilter() {
    const selector = document.getElementById('filter-sprint');
    if (!selector) return;

    selector.addEventListener('change', (e) => {
        filterSprintId = e.target.value ? parseInt(e.target.value) : null;
        if (filterSprintId) {
            window.history.pushState({}, '', `historial.html?sprint_id=${filterSprintId}`);
        } else {
            window.history.pushState({}, '', 'historial.html');
        }
        renderHistory();
    });
}

function renderHistory() {
    const container = document.getElementById('historial-content');
    if (!container) return;

    const sprintsToShow = filterSprintId
        ? currentSprints.filter(s => Number(s.id) === Number(filterSprintId))
        : currentSprints;

    const history = sprintsToShow
        .map(sprint => ({
            sprint,
            items: allItems.filter(item => Number(item.sprint_id) === Number(sprint.id))
        }))
        .filter(entry => entry.items.length > 0);

    if (history.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <h3>No hay retrospectivas registradas</h3>
                <p>${filterSprintId ? 'Este sprint no tiene items de retrospectiva' : 'Crea un sprint y registra tu primera retrospectiva'}</p>
                ${!filterSprintId ? '<a href="sprints.html" class="btn btn-primary" style="margin-top: 16px;"><i class="fas fa-plus"></i> Crear primer sprint</a>' : ''}
            </div>
        `;
        return;
    }

    container.innerHTML = history.map(({ sprint, items }) => {
        const totalItems = items.length;

        return `
            <article class="historial-sprint" aria-labelledby="sprint-title-${sprint.id}">
                <header class="historial-sprint-header">
                    <div>
                        <h3 id="sprint-title-${sprint.id}">${escapeHtml(sprint.nombre)}</h3>
                        <div class="historial-sprint-dates">
                            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                            ${formatDate(sprint.fecha_inicio)} - ${formatDate(sprint.fecha_fin)}
                            <span style="margin-left: 16px; color: var(--gray-500); font-size: 0.8rem;">
                                ${totalItems} item${totalItems !== 1 ? 's' : ''}
                            </span>
                        </div>
                    </div>
                    <a href="retrospectiva.html?sprint_id=${sprint.id}" class="btn btn-sm btn-add">
                        <i class="fas fa-comments" aria-hidden="true"></i> Ver retrospectiva
                    </a>
                </header>
                <div class="historial-categorias">
                    ${CATEGORY_ORDER.map(cat => {
                        const catItems = items.filter(item => item.categoria === cat);
                        if (catItems.length === 0) return '';
                        return `
                            <section class="historial-categoria" aria-labelledby="cat-${cat}-${sprint.id}">
                                <header class="historial-categoria-header ${cat}">
                                    <i class="fas fa-${CATEGORY_ICONS[cat]}" aria-hidden="true"></i>
                                    ${CATEGORY_LABELS[cat]}
                                    <span style="margin-left: 8px; font-weight: normal; color: var(--gray-500); font-size: 0.85rem;">
                                        (${catItems.length})
                                    </span>
                                </header>
                                <div class="historial-items">
                                    ${catItems.map(item => renderHistoryItem(item, cat)).join('')}
                                </div>
                            </section>
                        `;
                    }).join('')}
                </div>
            </article>
        `;
    }).join('');
}

function renderHistoryItem(item, cat) {
    const completed = isCompleted(item);

    return `
        <article class="historial-item ${cat} ${cat === 'accion' ? (completed ? 'cumplida' : 'no-cumplida') : ''}">
            <p style="margin-bottom: 6px;">${escapeHtml(item.descripcion)}</p>
            <div class="item-fecha" style="display: flex; gap: 12px; flex-wrap: wrap;">
                ${cat === 'accion' ? `
                    <span style="color: ${completed ? 'var(--success)' : 'var(--danger)'}">
                        <i class="fas fa-${completed ? 'check-circle' : 'times-circle'}" aria-hidden="true"></i>
                        ${completed ? 'Cumplida' : 'Pendiente'}
                    </span>
                    ${item.fecha_revision ? `<span><i class="fas fa-calendar-check" aria-hidden="true"></i> Revisar: ${formatDate(item.fecha_revision)}</span>` : ''}
                ` : ''}
                <span><i class="fas fa-clock" aria-hidden="true"></i> ${formatDateTime(item.created_at)}</span>
            </div>
        </article>
    `;
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