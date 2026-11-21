/**
 * Retrospectiva Page - Registro y seguimiento de items por sprint.
 */

import {
    getSprints,
    getItemsBySprint,
    deleteItem,
    getPendingActions,
    updateAction,
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
let previousActions = [];

const CATEGORY_ORDER = ['logro', 'impedimento', 'accion', 'comentario', 'otro'];

const CATEGORY_CONFIG = {
    logro: { icon: 'trophy', title: 'Logros (¿Qué funcionó bien?)', addLabel: 'Agregar logro' },
    impedimento: { icon: 'exclamation-triangle', title: 'Impedimentos (¿Qué necesita mejorar?)', addLabel: 'Agregar impedimento' },
    accion: { icon: 'tasks', title: 'Acciones (Compromisos para el próximo sprint)', addLabel: 'Agregar acción' },
    comentario: { icon: 'sticky-note', title: 'Comentarios adicionales', addLabel: 'Agregar comentario' },
    otro: { icon: 'circle', title: 'Otros', addLabel: 'Agregar otro' }
};

document.addEventListener('DOMContentLoaded', async () => {
    await checkServicesStatus();
    await loadSprints();
    setupSprintSelector();
    setupKeyboardShortcuts();

    const urlParams = new URLSearchParams(window.location.search);
    const sprintId = urlParams.get('sprint_id');
    if (sprintId) {
        const selector = document.getElementById('sprint-selector');
        selector.value = sprintId;
        await loadRetrospective(parseInt(sprintId));
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
            `<option value="${sprint.id}">${escapeHtml(sprint.nombre)} (${formatDate(sprint.fecha_inicio)} - ${formatDate(sprint.fecha_fin)})</option>`
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
        const [items, pending] = await Promise.all([
            getItemsBySprint(sprintId),
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

    const itemsByCategory = {};
    CATEGORY_ORDER.forEach(cat => { itemsByCategory[cat] = []; });
    items.forEach(item => {
        if (!itemsByCategory[item.categoria]) itemsByCategory[item.categoria] = [];
        itemsByCategory[item.categoria].push(item);
    });

    container.innerHTML = CATEGORY_ORDER.map(cat => {
        const config = CATEGORY_CONFIG[cat];
        const categoryItems = itemsByCategory[cat] || [];
        const addUrl = `item_form.html?categoria=${cat}&sprint_id=${currentSprintId}`;

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
                    ${categoryItems.length === 0 ? `
                        <div class="empty-state" style="padding: 30px 16px; border: 2px dashed var(--gray-300); border-radius: var(--radius);">
                            <i class="fas fa-${config.icon}" aria-hidden="true" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.3;"></i>
                            <p style="font-size: 0.9rem;">No hay ${getCategoryPlural(cat).toLowerCase()} registrados</p>
                            <a href="${addUrl}" class="btn btn-sm btn-add" style="margin-top: 8px;">
                                <i class="fas fa-plus" aria-hidden="true"></i> Agregar primero
                            </a>
                        </div>
                    ` : categoryItems.map(item => renderRetroItem(item)).join('')}
                </div>
            </section>
        `;
    }).join('');
}

function renderRetroItem(item) {
    const cat = item.categoria;
    const completed = isCompleted(item);
    const classes = ['retro-item', `categoria-${cat}`];
    if (cat === 'accion') classes.push(completed ? 'cumplida' : 'no-cumplida');

    const metaParts = [
        `<span><i class="fas fa-clock" aria-hidden="true"></i> ${formatDateTime(item.created_at)}</span>`
    ];

    if (cat === 'accion') {
        const icon = completed ? 'check-circle' : 'times-circle';
        const color = completed ? 'var(--success)' : 'var(--danger)';
        metaParts.push(`<span style="color: ${color}"><i class="fas fa-${icon}" aria-hidden="true"></i> ${completed ? 'Cumplida' : 'Pendiente'}</span>`);
        if (item.fecha_revision) {
            metaParts.push(`<span><i class="fas fa-calendar-check" aria-hidden="true"></i> Revisar: ${formatDate(item.fecha_revision)}</span>`);
        }
    }

    return `
        <article class="${classes.join(' ')}" data-id="${item.id}">
            <div class="item-icon" aria-hidden="true"><i class="fas fa-${getCategoryIcon(cat)}"></i></div>
            <div class="item-content">
                <div class="item-text">${escapeHtml(item.descripcion)}</div>
                <div class="item-meta">${metaParts.join(' ')}</div>
            </div>
            <div class="item-actions">
                <a href="item_form.html?id=${item.id}&categoria=${cat}&sprint_id=${currentSprintId}" class="btn btn-icon" aria-label="Editar ${getCategoryLabel(cat)}" title="Editar">
                    <i class="fas fa-edit" aria-hidden="true"></i>
                </a>
                <button class="btn btn-icon" aria-label="Eliminar ${getCategoryLabel(cat)}" title="Eliminar" onclick="confirmDeleteItem(${item.id})">
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
                        <label class="checkbox-label accion-anterior-form">
                            <input type="checkbox" class="accion-anterior-checkbox"
                                   onchange="updateActionStatus(event, ${action.id})"
                                   ${isCompleted(action) ? 'checked' : ''}
                                   aria-label="Marcar acción como cumplida">
                            <span class="accion-anterior-texto">${escapeHtml(action.descripcion)}</span>
                            <span class="accion-anterior-fecha">
                                ${escapeHtml(action.sprint?.nombre || 'Sprint anterior')}
                                ${action.fecha_revision ? ' · Revisar: ' + formatDate(action.fecha_revision) : ''}
                            </span>
                        </label>
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
    currentSprintId = null;
    previousActions = [];
}

async function updateActionStatus(event, actionId) {
    const checkbox = event.target;
    const cumplida = checkbox.checked;

    try {
        await updateAction(actionId, { cumplida });
        showToast(cumplida ? 'Acción marcada como cumplida' : 'Acción marcada como pendiente', 'success');
        if (currentSprintId) await loadRetrospective(currentSprintId);
    } catch (error) {
        console.error('Error updating action status:', error);
        showToast(error.message || 'Error al actualizar el estado', 'error');
        checkbox.checked = !cumplida;
    }
}

async function confirmDeleteItem(id) {
    if (!confirm('¿Está seguro de eliminar este item?')) return;

    try {
        await deleteItem(id);
        showToast('Item eliminado correctamente', 'success');
        if (currentSprintId) await loadRetrospective(currentSprintId);
    } catch (error) {
        console.error('Error deleting item:', error);
        showToast(error.message || 'Error al eliminar el item', 'error');
    }
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function (e) {
        if (e.altKey && !e.ctrlKey && !e.metaKey) {
            if (e.key === 's') { e.preventDefault(); window.location.href = 'sprints.html'; }
            if (e.key === 'r') { e.preventDefault(); window.location.href = 'retrospectiva.html'; }
            if (e.key === 'h') { e.preventDefault(); window.location.href = 'historial.html'; }
            if (e.key === 'd') { e.preventDefault(); window.location.href = 'dashboard.html'; }
        }
    });
}

function showLoading(show) {
    const main = document.getElementById('main-content');
    if (main) main.classList.toggle('loading', show);
}

window.confirmDeleteItem = confirmDeleteItem;
window.updateActionStatus = updateActionStatus;
