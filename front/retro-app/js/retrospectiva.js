/**
 * Retrospectiva Page - Registro y seguimiento de items por sprint.
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
    getCategoryLabel,
    getCategoryIcon,
    getCategoryPlural,
    isCompleted,
    escapeHtml,
    showToast
} from './api.js';

let currentSprints = [];
let currentSprintId = null;
let currentRetrospective = null;
let currentRetrospectiveId = null;
let previousActions = [];

const CATEGORY_ORDER = ['positive', 'negative', 'action', 'comment'];

const CATEGORY_CONFIG = {
    positive: { icon: 'trophy', title: 'Logros (¿Qué funcionó bien?)', addLabel: 'Agregar logro' },
    negative: { icon: 'exclamation-triangle', title: 'Impedimentos (¿Qué necesita mejorar?)', addLabel: 'Agregar impedimento' },
    action: { icon: 'tasks', title: 'Acciones (Compromisos para el próximo sprint)', addLabel: 'Agregar acción' },
    comment: { icon: 'sticky-note', title: 'Comentarios Adicionales', addLabel: 'Agregar comentario' }
};

const TYPE_TO_CATEGORY = {
    positive: 'logro',
    negative: 'impedimento',
    action: 'accion',
    comment: 'comentario'
};

document.addEventListener('DOMContentLoaded', async () => {
    await checkServicesStatus();
    await loadSprints();
    setupSprintSelector();

    // Check for sprint_id in URL
    const urlParams = new URLSearchParams(window.location.search);
    const sprintId = urlParams.get('sprint_id');
    if (sprintId && currentSprints.some(sprint => String(sprint.id) === sprintId)) {
        const selector = document.getElementById('sprint-selector');
        selector.value = sprintId;
        await loadRetrospective(parseInt(sprintId));
    } else if (sprintId) {
        showToast('El sprint solicitado no existe', 'error');
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

async function loadSprints() {
    try {
        currentSprints = await getSprints();
        renderSprintSelector();
    } catch (error) {
        console.error('Error loading sprints:', error);
        showToast('Error al cargar sprints', 'error');
    }
}

function renderSprintSelector() {
    const selector = document.getElementById('sprint-selector');
    if (!selector) return;

    const selected = selector.value;
    selector.innerHTML = '<option value="">-- Seleccionar Sprint --</option>' +
        currentSprints.map(sprint =>
            `<option value="${sprint.id}">${escapeHtml(sprint.nombre)} (${formatDate(sprint.start_date)} - ${formatDate(sprint.end_date)})</option>`
        ).join('');

    if (selected) selector.value = selected;
}

function setupSprintSelector() {
    const selector = document.getElementById('sprint-selector');
    if (!selector) return;

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

async function loadRetrospective(sprintId) {
    currentSprintId = sprintId;
    showLoading(true);

    try {
        let retrospective = await getRetrospectiveBySprint(sprintId);
        if (!retrospective) {
            retrospective = await createRetrospective({
                sprint_id: sprintId,
                date: new Date().toISOString().slice(0, 10)
            });
        }

        currentRetrospective = retrospective;
        currentRetrospectiveId = retrospective.id;

        const [items, pending] = await Promise.all([
            getFeedbackByRetrospective(retrospective.id),
            getPendingActions(sprintId).catch(() => [])
        ]);

        previousActions = pending || [];
        renderRetrospective(items || []);
        renderPreviousActions();
    } catch (error) {
        console.error('Error loading retrospective:', error);
        showToast(error.message || 'Error al cargar la retrospectiva', 'error');
    } finally {
        showLoading(false);
    }
}

function renderRetrospective(items) {
    const container = document.getElementById('retro-content');
    if (!container) return;

    // Group items by type (API uses: positive, negative, action, comment)
    const itemsByType = {
        positive: [],
        negative: [],
        action: [],
        comment: []
    };

    items.forEach(item => {
        if (!itemsByType[item.type]) itemsByType[item.type] = [];
        itemsByType[item.type].push(item);
    });

    // Map API types to UI categories
    const CATEGORY_CONFIG = {
        logro: { icon: 'trophy', title: 'Logros (¿Qué funcionó bien?)', addLabel: 'Agregar logro', type: 'positive' },
        impedimento: { icon: 'exclamation-triangle', title: 'Impedimentos (¿Qué necesita mejorar?)', addLabel: 'Agregar impedimento', type: 'negative' },
        accion: { icon: 'tasks', title: 'Acciones (Compromisos para el próximo sprint)', addLabel: 'Agregar acción', type: 'action' },
        comentario: { icon: 'sticky-note', title: 'Comentarios Adicionales', addLabel: 'Agregar comentario', type: 'comment' }
    };

    container.innerHTML = Object.entries(CATEGORY_CONFIG).map(([cat, config]) => {
        const type = config.type;
        const typeItems = itemsByType[type] || [];
        const addUrl = `item_form.html?categoria=${cat}&sprint_id=${currentSprintId}&retro_id=${currentRetrospectiveId}`;

        return `
            <section class="retro-section" aria-labelledby="section-${cat}">
                <div class="section-header">
                    <h3 class="${cat}" id="section-${cat}">
                        <i class="fas fa-${config.icon}" aria-hidden="true"></i> ${config.title}
                    </h3>
                    <a href="${addUrl}" class="btn btn-sm btn-add">
                        <i class="fas fa-plus" aria-hidden="true"></i> ${config.addLabel}
                    </a>
                </div>
                <div class="items-container" id="${cat}-container">
                    ${typeItems.length === 0 ? `
                        <div class="empty-state" style="padding: 30px 16px; border: 2px dashed var(--gray-300); border-radius: var(--radius);">
                            <i class="fas fa-${config.icon}" aria-hidden="true" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.3;"></i>
                            <p style="font-size: 0.9rem;">No hay ${config.title.toLowerCase()} registrados</p>
                            <a href="${addUrl}" class="btn btn-sm btn-add" style="margin-top: 8px;">
                                <i class="fas fa-plus" aria-hidden="true"></i> Agregar primero
                            </a>
                        </div>
                    ` : typeItems.map(item => renderRetroItem(item, currentSprintId)).join('')}
                </div>
            </section>
        `;
    }).join('');
}

function renderRetroItem(item, sprintId) {
    const cat = TYPE_TO_CATEGORY[item.type] || 'comentario';
    const completed = isCompleted(item);
    const classes = ['retro-item', `categoria-${cat}`];
    if (item.type === 'action') classes.push(completed ? 'cumplida' : 'no-cumplida');

    const icons = {
        positive: 'trophy',
        negative: 'exclamation-triangle',
        action: 'tasks',
        comment: 'sticky-note'
    };

    let metaParts = [
        `<span><i class="fas fa-clock" aria-hidden="true"></i> ${formatDateTime(item.created_at)}</span>`
    ];

    if (item.type === 'action') {
        const icon = completed ? 'check-circle' : 'times-circle';
        const color = completed ? 'var(--success)' : 'var(--danger)';
        metaParts.push(`<span style="color: ${color}"><i class="fas fa-${icon}" aria-hidden="true"></i> ${completed ? 'Cumplida' : 'Pendiente'}</span>`);
        if (item.due_date) {
            metaParts.push(`<span><i class="fas fa-calendar-check" aria-hidden="true"></i> Revisar: ${formatDate(item.due_date)}</span>`);
        }
    }

    const catLabels = {
        logro: 'Logro',
        impedimento: 'Impedimento',
        accion: 'Acción',
        comentario: 'Comentario'
    };

    return `
        <article class="${classes.join(' ')}" data-id="${item.id}">
            <div class="item-icon" aria-hidden="true"><i class="fas fa-${icons[item.type] || 'circle'}"></i></div>
            <div class="item-content">
                <div class="item-text">${escapeHtml(item.description || item.title)}</div>
                <div class="item-meta">${metaParts.join(' ')}</div>
            </div>
            <div class="item-actions">
                <a href="item_form.html?id=${item.id}&categoria=${TYPE_TO_CATEGORY[item.type]}&sprint_id=${sprintId}&retro_id=${currentRetrospectiveId}" class="btn btn-icon" aria-label="Editar ${catLabels[cat]}" title="Editar">
                    <i class="fas fa-edit" aria-hidden="true"></i>
                </a>
                <button class="btn btn-icon" aria-label="Eliminar ${catLabels[cat]}" title="Eliminar" onclick="confirmDeleteItem(${item.id})">
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
                        <form class="accion-anterior-form" onsubmit="updateActionStatus(event, ${action.id}, ${currentSprintId})">
                            <input type="hidden" name="id" value="${action.id}">
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

async function updateActionStatus(event, feedbackId, sprintId) {
    event.preventDefault();
    const form = event.target;
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
        form.cumplida.checked = !cumplida;
    }
}

async function confirmDeleteItem(id) {
    if (!confirm('¿Está seguro de eliminar este item?')) return;

    try {
        await deleteFeedback(id);
        showToast('Item eliminado correctamente', 'success');
        if (currentSprintId) await loadRetrospective(currentSprintId);
    } catch (error) {
        console.error('Error deleting item:', error);
        showToast('Error al eliminar el item', 'error');
    }
}

function showLoading(show) {
    const main = document.getElementById('main-content');
    if (main) main.classList.toggle('loading', show);
}

window.confirmDeleteItem = confirmDeleteItem;
window.updateActionStatus = updateActionStatus;
window.showToast = showToast;