/**
 * Historial Page - View all retrospectives history
 */

import { 
    getSprints, 
    getRetrospectiveBySprint, 
    getFeedbackByRetrospective,
    checkAllServices,
    formatDate,
    formatDateTime,
    getTypeLabel,
    getTypeIcon,
    getTypeColor,
    showToast 
} from './api.js';

let currentSprints = [];
let allHistory = [];
let filterSprintId = null;

document.addEventListener('DOMContentLoaded', async () => {
    await checkServicesStatus();
    await loadSprints();
    setupFilter();
    setupKeyboardShortcuts();
    
    // Check for sprint_id in URL
    const urlParams = new URLSearchParams(window.location.search);
    const sprintId = urlParams.get('sprint_id');
    if (sprintId) {
        document.getElementById('filter-sprint').value = sprintId;
        filterSprintId = parseInt(sprintId);
        await loadHistory(filterSprintId);
    } else {
        await loadHistory();
    }
});

async function checkServicesStatus() {
    const status = await checkAllServices();
    const container = document.getElementById('services-status');
    if (container) {
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
}

async function loadSprints() {
    try {
        currentSprints = await getSprints() || [];
        renderSprintFilter();
    } catch (error) {
        console.error('Error loading sprints:', error);
        showToast('Error al cargar sprints', 'error');
    }
}

function renderSprintFilter() {
    const selector = document.getElementById('filter-sprint');
    if (!selector) return;

    selector.innerHTML = '<option value="">-- Todos los sprints --</option>' +
        currentSprints.map(sprint => 
            `<option value="${sprint.id}">${escapeHtml(sprint.name)} (${formatDate(sprint.start_date)} - ${formatDate(sprint.end_date)})</option>`
        ).join('');
}

function setupFilter() {
    const selector = document.getElementById('filter-sprint');
    if (selector) {
        selector.addEventListener('change', async (e) => {
            const sprintId = e.target.value ? parseInt(e.target.value) : null;
            filterSprintId = sprintId;
            if (sprintId) {
                window.history.pushState({}, '', `historial.html?sprint_id=${sprintId}`);
            } else {
                window.history.pushState({}, '', 'historial.html');
            }
            await loadHistory(sprintId);
        });
    }
}

async function loadHistory(sprintId = null) {
    showLoading(true);
    
    try {
        let sprintsToLoad = sprintId ? [currentSprints.find(s => s.id === sprintId)] : currentSprints;
        sprintsToLoad = sprintsToLoad.filter(Boolean);

        allHistory = [];
        
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
        
        renderHistory();
        
    } catch (error) {
        console.error('Error loading history:', error);
        showToast('Error al cargar el historial', 'error');
    } finally {
        showLoading(false);
    }
}

function renderHistory() {
    const container = document.getElementById('historial-content');
    if (!container) return;

    if (allHistory.length === 0) {
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

    const categoryOrder = ['positive', 'negative', 'action', 'comment'];
    const categoryLabels = {
        'positive': 'Logros',
        'negative': 'Impedimentos',
        'action': 'Acciones',
        'comment': 'Comentarios'
    };
    const categoryIcons = {
        'positive': 'trophy',
        'negative': 'exclamation-triangle',
        'action': 'tasks',
        'comment': 'sticky-note'
    };

    container.innerHTML = allHistory.map(({ sprint, retrospective, items }) => {
        // Count items per category
        const catCounts = { positive: 0, negative: 0, action: 0, comment: 0 };
        items.forEach(item => { if (catCounts[item.type] !== undefined) catCounts[item.type]++; });
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
                    ${categoryOrder.map(cat => {
                        const catItems = items.filter(item => item.type === cat);
                        if (catItems.length === 0) return '';
                        return `
                            <section class="historial-categoria" aria-labelledby="cat-${cat}-${sprint.id}">
                                <header class="historial-categoria-header ${cat}">
                                    <i class="fas fa-${categoryIcons[cat]}" aria-hidden="true"></i>
                                    ${categoryLabels[cat]} 
                                    <span style="margin-left: 8px; font-weight: normal; color: var(--gray-500); font-size: 0.85rem;">
                                        (${catItems.length})
                                    </span>
                                </header>
                                <div class="historial-items">
                                    ${catItems.map(item => `
                                        <article class="historial-item ${cat} ${cat === 'action' ? (item.cumplida ? 'cumplida' : 'no-cumplida') : ''}">
                                            <p style="margin-bottom: 6px;">${escapeHtml(item.description || item.title)}</p>
                                            ${cat === 'action' ? `
                                                <div class="item-fecha" style="display: flex; gap: 12px; flex-wrap: wrap;">
                                                    <span style="color: ${item.cumplida ? 'var(--success)' : 'var(--danger)'}">
                                                        <i class="fas fa-${item.cumplida ? 'check-circle' : 'times-circle'}" aria-hidden="true"></i>
                                                        ${item.cumplida ? 'Cumplida' : 'Pendiente'}
                                                    </span>
                                                    ${item.fecha_revision ? `<span><i class="fas fa-calendar-check" aria-hidden="true"></i> Revisar: ${formatDate(item.fecha_revision)}</span>` : ''}
                                                    <span><i class="fas fa-clock" aria-hidden="true"></i> ${formatDateTime(item.created_at)}</span>
                                                </div>
                                            ` : `
                                                <div class="item-fecha">
                                                    <i class="fas fa-clock" aria-hidden="true"></i> ${formatDateTime(item.created_at)}
                                                </div>
                                            `}
                                        </article>
                                    `).join('')}
                                </div>
                            </section>
                        `;
                    }).join('')}
                </div>
            </article>
        `;
    }).join('');
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        if (e.altKey && !e.ctrlKey && !e.metaKey) {
            if (e.key === 's') { e.preventDefault(); window.location.href = 'sprints.html'; }
            if (e.key === 'r') { e.preventDefault(); window.location.href = 'retrospectiva.html'; }
            if (e.key === 'h') { e.preventDefault(); window.location.href = 'historial.html'; }
            if (e.key === 'd') { e.preventDefault(); window.location.href = 'dashboard.html'; }
        }
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active, .toast').forEach(el => el.remove());
        }
    });
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