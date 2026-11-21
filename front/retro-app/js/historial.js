/**
 * Historial Page - Historial de retrospectivas por sprint.
 */

import {
    getSprints,
    getRetrospectiveBySprint,
    getFeedbackByRetrospective,
    checkAllServices,
    formatDate,
    formatDateTime,
    isCompleted
} from './api.js';

let currentSprints = [];
let filterSprintId = null;

const CATEGORY_ORDER = ['positive', 'negative', 'action', 'comment'];
const CATEGORY_LABELS = {
    positive: 'Logros',
    negative: 'Impedimentos',
    action: 'Acciones',
    comment: 'Comentarios'
};
const CATEGORY_ICONS = {
    positive: 'trophy',
    negative: 'exclamation-triangle',
    action: 'tasks',
    comment: 'sticky-note'
};

document.addEventListener('DOMContentLoaded', async () => {
    await checkServicesStatus();

    // Check for sprint_id in URL
    const urlParams = new URLSearchParams(window.location.search);
    const sprintId = urlParams.get('sprint_id');
    filterSprintId = sprintId ? parseInt(sprintId, 10) : null;
    await loadData();
    setupFilter();
    if (filterSprintId) document.getElementById('filter-sprint').value = filterSprintId;
});

async function checkServicesStatus() {
    const status = await checkAllServices();
    const container = document.getElementById('services-status');
    if (!container) return;

    container.innerHTML = `
        <div class="service-badge ${status.sprints ? 'online' : 'offline'}">
            <i class="fas fa-calendar-alt"></i> Sprint ${status.sprints ? '●' : '○'}
        </div>
        <div class="service-badge ${status.retrospectives ? 'online' : 'offline'}">
            <i class="fas fa-comments"></i> Retro ${status.retrospectives ? '●' : '○'}
        </div>
        <div class="service-badge ${status.feedback ? 'online' : 'offline'}">
            <i class="fas fa-list"></i> Feedback ${status.feedback ? '●' : '○'}
        </div>
    `;
}

async function loadData() {
    showLoading(true);
    try {
        const [sprints] = await Promise.all([getSprints()]);
        currentSprints = sprints || [];
        renderSprintFilter();
        await loadHistory(filterSprintId);
    } catch (error) {
        console.error('Error loading history:', error);
        showToast(error.message || 'Error al cargar el historial', 'error');
    } finally {
        showLoading(false);
    }
}

async function loadHistory(sprintId = null) {
    showLoading(true);
    
    try {
        let sprintsToLoad = sprintId ? [currentSprints.find(s => s.id === sprintId)] : currentSprints;
        sprintsToLoad = sprintsToLoad.filter(Boolean);

        let allHistory = [];
        
        for (const sprint of sprintsToLoad) {
            try {
                const retrospective = await getRetrospectiveBySprint(sprint.id);
                if (retrospective) {
                    const items = await getFeedbackByRetrospective(retrospective.id);
                    items.forEach(item => {
                        item.sprint_nombre = sprint.name;
                        item.sprint_fecha_inicio = sprint.start_date;
                        item.sprint_fecha_fin = sprint.end_date;
                    });
                    allHistory.push({ sprint, retrospective, items });
                }
            } catch (e) {
                // No retrospective for this sprint
            }
        }

        // Sort by sprint date descending
        allHistory.sort((a, b) => new Date(b.sprint.start_date) - new Date(a.sprint.start_date));
        
        renderHistory(allHistory);
        
    } catch (error) {
        console.error('Error loading history:', error);
        showToast('Error al cargar el historial', 'error');
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
            `<option value="${sprint.id}">${escapeHtml(sprint.name)} (${formatDate(sprint.start_date)} - ${formatDate(sprint.end_date)})</option>`
        ).join('');

    if (selected) selector.value = selected;
}

function setupFilter() {
    const selector = document.getElementById('filter-sprint');
    if (!selector) return;

    selector.addEventListener('change', async (e) => {
        const sprintId = e.target.value ? parseInt(e.target.value) : null;
        filterSprintId = sprintId;
        if (sprintId) {
            window.history.pushState({}, '', `historial.html?sprint_id=${filterSprintId}`);
        } else {
            window.history.pushState({}, '', 'historial.html');
        }
        await loadHistory(sprintId);
    });
}

function renderHistory(history) {
    const container = document.getElementById('historial-content');
    if (!container) return;

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

    container.innerHTML = history.map(({ sprint, retrospective, items }) => {
        const totalItems = items.length;

        return `
            <article class="historial-sprint" aria-labelledby="sprint-title-${sprint.id}">
                <header class="historial-sprint-header">
                    <div>
                        <h3 id="sprint-title-${sprint.id}">${escapeHtml(sprint.name)}</h3>
                        <div class="historial-sprint-dates">
                            <i class="fas fa-calendar-alt" aria-hidden="true"></i> 
                            ${formatDate(sprint.start_date)} - ${formatDate(sprint.end_date)}
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
                    ${['positive', 'negative', 'action', 'comment'].map(cat => {
                        const catItems = items.filter(item => item.type === cat);
                        if (catItems.length === 0) return '';
                        return `
                            <section class="historial-categoria" aria-labelledby="cat-${cat}-${sprint.id}">
                                <header class="historial-categoria-header ${cat}">
                                    <i class="fas fa-${cat === 'positive' ? 'trophy' : cat === 'negative' ? 'exclamation-triangle' : cat === 'action' ? 'tasks' : 'sticky-note'}" aria-hidden="true"></i>
                                    ${cat === 'positive' ? 'Logros' : cat === 'negative' ? 'Impedimentos' : cat === 'action' ? 'Acciones' : 'Comentarios'}
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
    const completed = item.cumplida === true || item.cumplida === 1 || item.cumplida === '1';

    return `
        <article class="historial-item ${cat} ${cat === 'action' ? (completed ? 'cumplida' : 'no-cumplida') : ''}">
            <p style="margin-bottom: 6px;">${escapeHtml(item.description || item.title)}</p>
            ${cat === 'action' ? `
                <div class="item-fecha" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <span style="color: ${completed ? 'var(--success)' : 'var(--danger)'}">
                        <i class="fas fa-${completed ? 'check-circle' : 'times-circle'}" aria-hidden="true"></i>
                        ${completed ? 'Cumplida' : 'Pendiente'}
                    </span>
                    ${item.due_date ? `<span><i class="fas fa-calendar-check" aria-hidden="true"></i> Revisar: ${formatDate(item.due_date)}</span>` : ''}
                    <span><i class="fas fa-clock" aria-hidden="true"></i> ${formatDateTime(item.created_at)}</span>
                </div>
            ` : `
                <div class="item-fecha">
                    <i class="fas fa-clock" aria-hidden="true"></i> ${formatDateTime(item.created_at)}
                </div>
            `}
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