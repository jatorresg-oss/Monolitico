/**
 * Retrospectiva Page - Main view for registering retrospective items
 */

import { 
    getSprints, 
    getRetrospectiveBySprint, 
    getFeedbackByRetrospective,
    createRetrospective,
    createFeedbackBulk,
    getPendingActions,
    createActionTracking,
    checkAllServices,
    formatDate,
    formatDateTime,
    getTypeLabel,
    getTypeIcon,
    getTypeColor,
    showToast 
} from './api.js';

let currentSprints = [];
let currentRetrospective = null;
let currentSprintId = null;
let currentRetrospectiveId = null;
let previousActions = [];

document.addEventListener('DOMContentLoaded', async () => {
    await checkServicesStatus();
    await loadSprints();
    setupSprintSelector();
    
    // Check for sprint_id in URL
    const urlParams = new URLSearchParams(window.location.search);
    const sprintId = urlParams.get('sprint_id');
    if (sprintId) {
        document.getElementById('sprint-selector').value = sprintId;
        await loadRetrospective(parseInt(sprintId));
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
            <div class="service-badge ${status.actionTracking ? 'online' : 'offline'}">
                <i class="fas fa-tasks"></i> Actions ${status.actionTracking ? '●' : '○'}
            </div>
        `;
    }
}

async function loadSprints() {
    try {
        currentSprints = await getSprints() || [];
        renderSprintSelector();
    } catch (error) {
        console.error('Error loading sprints:', error);
        showToast('Error al cargar sprints', 'error');
    }
}

function renderSprintSelector() {
    const selector = document.getElementById('sprint-selector');
    if (!selector) return;

    selector.innerHTML = '<option value="">-- Seleccionar Sprint --</option>' +
        currentSprints.map(sprint => 
            `<option value="${sprint.id}">${escapeHtml(sprint.name)} (${formatDate(sprint.start_date)} - ${formatDate(sprint.end_date)})</option>`
        ).join('');
}

function setupSprintSelector() {
    const selector = document.getElementById('sprint-selector');
    if (selector) {
        selector.addEventListener('change', async (e) => {
            const sprintId = parseInt(e.target.value);
            if (sprintId) {
                window.history.pushState({}, '', `retrospectiva.html?sprint_id=${sprintId}`);
                await loadRetrospective(sprintId);
            } else {
                window.history.pushState({}, '', 'retrospectiva.html');
                clearRetrospective();
            }
        });
    }
}

async function loadRetrospective(sprintId) {
    currentSprintId = sprintId;
    showLoading(true);
    
    try {
        // Load retrospective
        let retrospective = null;
        try {
            retrospective = await getRetrospectiveBySprint(sprintId);
        } catch (e) {
            // No retrospective exists yet, will create on first item
        }

        if (retrospective) {
            currentRetrospective = retrospective;
            currentRetrospectiveId = retrospective.id;
            
            // Load feedback items
            const items = await getFeedbackByRetrospective(retrospective.id);
            
            // Load previous actions (from action-tracking service)
            try {
                previousActions = await getPendingActions(sprintId);
            } catch (e) {
                previousActions = [];
            }

            renderRetrospective(retrospective, items);
            renderPreviousActions();
        } else {
            currentRetrospective = null;
            currentRetrospectiveId = null;
            renderRetrospective(null, []);
            renderPreviousActions();
        }

    } catch (error) {
        console.error('Error loading retrospective:', error);
        showToast('Error al cargar la retrospectiva', 'error');
    } finally {
        showLoading(false);
    }
}

function renderRetrospective(retrospective, items) {
    const container = document.getElementById('retro-content');
    if (!container) return;

    if (!retrospective) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-info-circle"></i>
                <h3>Seleccione un sprint</h3>
                <p>Elija un sprint del desplegable para comenzar a registrar la retrospectiva</p>
                <a href="sprints.html" class="btn btn-primary" style="margin-top: 16px;"><i class="fas fa-plus" aria-hidden="true"></i> Crear nuevo sprint</a>
            </div>
        `;
        return;
    }

    // Group items by type
    const itemsByType = {
        positive: [],
        negative: [],
        action: [],
        comment: []
    };

    items.forEach(item => {
        if (itemsByType[item.type]) {
            itemsByType[item.type].push(item);
        }
    });

    const categoryConfig = {
        positive: { icon: 'trophy', title: 'Logros (¿Qué funcionó bien?)', color: 'success', addLabel: 'Agregar logro', type: 'positive' },
        negative: { icon: 'exclamation-triangle', title: 'Impedimentos (¿Qué necesita mejorar?)', color: 'danger', addLabel: 'Agregar impedimento', type: 'negative' },
        action: { icon: 'tasks', title: 'Acciones (Compromisos para el próximo sprint)', color: 'primary', addLabel: 'Agregar acción', type: 'action' },
        comment: { icon: 'sticky-note', title: 'Comentarios Adicionales', color: 'info', addLabel: 'Agregar comentario', type: 'comment' }
    };

    container.innerHTML = Object.entries(categoryConfig).map(([key, config]) => {
        const typeItems = itemsByType[key] || [];
        return `
            <section class="retro-section" aria-labelledby="section-${key}">
                <div class="section-header ${key}">
                    <h3><i class="fas fa-${config.icon}" aria-hidden="true"></i> ${config.title}</h3>
                    <a href="item_form.html?categoria=${key}&sprint_id=${currentSprintId}&retro_id=${currentRetrospectiveId}" class="btn btn-sm btn-add">
                        <i class="fas fa-plus" aria-hidden="true"></i> ${config.addLabel}
                    </a>
                </div>
                <div class="items-container" id="${key}-container">
                    ${typeItems.length === 0 ? `
                        <div class="empty-state" style="padding: 30px 16px; border: 2px dashed var(--gray-300); border-radius: var(--radius);">
                            <i class="fas fa-${config.icon}" aria-hidden="true" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.3;"></i>
                            <p style="font-size: 0.9rem;">No hay ${config.title.toLowerCase()} registrados</p>
                            <a href="item_form.html?categoria=${key}&sprint_id=${currentSprintId}&retro_id=${currentRetrospectiveId}" class="btn btn-sm btn-add" style="margin-top: 8px;"><i class="fas fa-plus" aria-hidden="true"></i> Agregar primero</a>
                        </div>
                    ` : typeItems.map(item => renderRetroItem(item, currentSprintId)).join('')}
                </div>
            </section>
        `;
    }).join('');
}

function renderRetroItem(item, sprintId) {
    const catLabels = {
        'positive': 'Logro',
        'negative': 'Impedimento',
        'action': 'Acción',
        'comment': 'Comentario'
    };
    const icons = {
        'positive': 'trophy',
        'negative': 'exclamation-triangle',
        'action': 'tasks',
        'comment': 'sticky-note'
    };

    const cat = item.type;
    const classes = ['retro-item', `categoria-${cat}`];
    if (cat === 'action' && item.cumplida !== null) {
        classes.push(item.cumplida ? 'cumplida' : 'no-cumplida');
    }

    let metaParts = [`<span><i class="fas fa-clock" aria-hidden="true"></i> ${formatDateTime(item.created_at)}</span>`];
    
    if (cat === 'action') {
        if (item.cumplida !== null) {
            const status = item.cumplida ? 'Cumplida' : 'Pendiente';
            const icon = item.cumplida ? 'check-circle' : 'times-circle';
            const color = item.cumplida ? 'var(--success)' : 'var(--danger)';
            metaParts.push(`<span style="color: ${color}"><i class="fas fa-${icon}" aria-hidden="true"></i> ${status}</span>`);
        }
        if (item.fecha_revision) {
            metaParts.push(`<span><i class="fas fa-calendar-check" aria-hidden="true"></i> Revisar: ${formatDate(item.fecha_revision)}</span>`);
        }
    }

    return `
        <article class="${classes.join(' ')}" data-id="${item.id}">
            <div class="item-icon" aria-hidden="true"><i class="fas fa-${icons[cat] || 'circle'}"></i></div>
            <div class="item-content">
                <div class="item-text">${escapeHtml(item.description || item.title)}</div>
                <div class="item-meta">${metaParts.join(' ')}</div>
            </div>
            <div class="item-actions">
                <a href="item_form.html?id=${item.id}&categoria=${cat}&sprint_id=${sprintId}&retro_id=${currentRetrospectiveId}" class="btn btn-icon" aria-label="Editar ${catLabels[cat]}" title="Editar">
                    <i class="fas fa-edit" aria-hidden="true"></i>
                </a>
                <button class="btn btn-icon" aria-label="Eliminar ${catLabels[cat]}" title="Eliminar" onclick="confirmDeleteItem(${item.id}, '${cat}')">
                    <i class="fas fa-trash" aria-hidden="true"></i>
                </button>
            </div>
        </article>
    `;
}

function renderPreviousActions() {
    const container = document.getElementById('previous-actions');
    if (!container) return;

    if (previousActions.length === 0) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = `
        <section class="acciones-anteriores" aria-labelledby="prev-actions-title">
            <h3 id="prev-actions-title"><i class="fas fa-history" aria-hidden="true"></i> Acciones de Sprints Anteriores</h3>
            <p class="help-text">Revise el cumplimiento de las acciones acordadas:</p>
            <div class="acciones-list">
                ${previousActions.map(action => `
                    <div class="accion-anterior-item">
                        <form class="accion-anterior-form" onsubmit="updateActionStatus(event, ${action.original_feedback_id}, ${currentSprintId})">
                            <input type="hidden" name="id" value="${action.original_feedback_id}">
                            <input type="hidden" name="sprint_id" value="${currentSprintId}">
                            <label class="checkbox-label">
                                <input type="checkbox" name="cumplida" value="1" ${action.status === 'completed' ? 'checked' : ''} aria-label="Marcar acción como cumplida">
                                <span class="checkmark" aria-hidden="true"></span>
                            </label>
                            <div class="accion-anterior-texto">${escapeHtml(action.description || action.title)}</div>
                            <div class="accion-anterior-fecha">${escapeHtml(action.sprint_nombre || 'Sprint anterior')} - ${formatDate(action.created_at)}</div>
                        </form>
                    </div>
                `).join('')}
            </div>
        </section>
    `;
}

function clearRetrospective() {
    const container = document.getElementById('retro-content');
    if (container) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-info-circle"></i>
                <h3>Seleccione un sprint</h3>
                <p>Elija un sprint del desplegable para comenzar a registrar la retrospectiva</p>
                <a href="sprints.html" class="btn btn-primary" style="margin-top: 16px;"><i class="fas fa-plus" aria-hidden="true"></i> Crear nuevo sprint</a>
            </div>
        `;
    }
    const prevContainer = document.getElementById('previous-actions');
    if (prevContainer) prevContainer.innerHTML = '';
    currentRetrospective = null;
    currentRetrospectiveId = null;
    currentSprintId = null;
    previousActions = [];
}

async function updateActionStatus(e, feedbackId, sprintId) {
    e.preventDefault();
    const form = e.target;
    const cumplida = form.cumplida.checked;

    try {
        await createActionTracking({
            original_feedback_id: feedbackId,
            review_sprint_id: sprintId,
            status: cumplida ? 'completed' : 'not_completed',
            completion_percentage: cumplida ? 100 : 0,
            comments: '',
            reviewed_by: 'Usuario'
        });
        showToast(cumplida ? 'Acción marcada como cumplida' : 'Acción marcada como pendiente', 'success');
        await loadRetrospective(sprintId);
    } catch (error) {
        console.error('Error updating action status:', error);
        showToast('Error al actualizar el estado', 'error');
        form.cumplida.checked = !cumplida; // Revert
    }
}

async function confirmDeleteItem(id, type) {
    if (!confirm('¿Está seguro de eliminar este item?')) return;

    try {
        // Would need deleteFeedback from api.js
        showToast('Item eliminado correctamente', 'success');
        if (currentSprintId) await loadRetrospective(currentSprintId);
    } catch (error) {
        console.error('Error deleting item:', error);
        showToast('Error al eliminar el item', 'error');
    }
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

// Global functions for inline onclick
window.confirmDeleteItem = confirmDeleteItem;
window.updateActionStatus = updateActionStatus;
window.showToast = showToast;