/**
 * Sprints Management Page
 */

import {
    getSprints,
    getAllItems,
    createSprint,
    updateSprint,
    deleteSprint,
    checkAllServices,
    formatDate,
    escapeHtml,
    showToast
} from './api.js';

let currentSprints = [];
let allItems = [];
let currentSprintId = null;

document.addEventListener('DOMContentLoaded', async () => {
    await checkServicesStatus();
    await loadSprints();
    setupForm();
    setupKeyboardShortcuts();

    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    if (id) {
        const sprint = currentSprints.find(s => Number(s.id) === Number(id));
        if (sprint) openSprintModal(sprint.id);
    }
});

async function checkServicesStatus() {
    const status = await checkAllServices();
    const container = document.getElementById('services-status');
    if (!container) return;

    container.innerHTML = `
        <div class="service-badge ${status.sprints ? 'online' : 'offline'}">
            <i class="fas fa-calendar-alt"></i> Sprint Service ${status.sprints ? '●' : '○'}
        </div>
        <div class="service-badge ${status.retrospective ? 'online' : 'offline'}">
            <i class="fas fa-comments"></i> Retrospective Service ${status.retrospective ? '●' : '○'}
        </div>
    `;
}

async function loadSprints() {
    showLoading(true);
    try {
        const [sprints, items] = await Promise.all([getSprints(), getAllItems()]);
        currentSprints = sprints || [];
        allItems = items || [];
        renderSprints();
    } catch (error) {
        console.error('Error loading sprints:', error);
        showToast('Error al cargar sprints', 'error');
    } finally {
        showLoading(false);
    }
}

function renderSprints() {
    const container = document.getElementById('sprints-list');
    if (!container) return;

    if (currentSprints.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-plus"></i>
                <h3>No hay sprints registrados</h3>
                <p>Crea tu primer sprint para empezar a registrar retrospectivas</p>
                <button class="btn btn-primary" onclick="openSprintModal()"><i class="fas fa-plus"></i> Crear primer sprint</button>
            </div>
        `;
        return;
    }

    container.innerHTML = currentSprints.map(sprint => {
        const items = allItems.filter(item => Number(item.sprint_id) === Number(sprint.id));
        const count = categoria => items.filter(item => item.categoria === categoria).length;

        return `
            <article class="sprint-card" data-sprint-id="${sprint.id}">
                <div class="sprint-card-header">
                    <h3>${escapeHtml(sprint.nombre)}</h3>
                    <div class="sprint-card-actions">
                        <a href="retrospectiva.html?sprint_id=${sprint.id}" class="btn btn-sm btn-add">
                            <i class="fas fa-comments"></i> Retrospectiva
                        </a>
                        <button class="btn btn-secondary btn-sm" onclick="openSprintModal(${sprint.id})">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="confirmDeleteSprint(${sprint.id})">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
                <div class="sprint-card-dates">
                    <span><i class="fas fa-play"></i> ${formatDate(sprint.fecha_inicio)}</span>
                    <span><i class="fas fa-stop"></i> ${formatDate(sprint.fecha_fin)}</span>
                </div>
                <div class="sprint-card-stats">
                    <span class="stat-item"><i class="fas fa-trophy"></i> ${count('logro')} logros</span>
                    <span class="stat-item"><i class="fas fa-exclamation-triangle"></i> ${count('impedimento')} impedimentos</span>
                    <span class="stat-item"><i class="fas fa-tasks"></i> ${count('accion')} acciones</span>
                    <span class="stat-item"><i class="fas fa-sticky-note"></i> ${count('comentario')} comentarios</span>
                </div>
            </article>
        `;
    }).join('');
}

function openSprintModal(id = null) {
    currentSprintId = id;
    const modal = document.getElementById('sprint-modal');
    const form = document.getElementById('sprint-form');
    const title = document.getElementById('modal-title');

    form.reset();
    document.getElementById('sprint-id').value = '';

    if (id) {
        title.innerHTML = '<i class="fas fa-edit"></i> Modificar Sprint';
        const sprint = currentSprints.find(s => Number(s.id) === Number(id));
        if (sprint) {
            document.getElementById('sprint-id').value = sprint.id;
            document.getElementById('sprint-name').value = sprint.nombre;
            document.getElementById('sprint-start').value = sprint.fecha_inicio;
            document.getElementById('sprint-end').value = sprint.fecha_fin;
        }
    } else {
        title.innerHTML = '<i class="fas fa-plus"></i> Nuevo Sprint';
    }

    modal.classList.add('active');
    document.getElementById('sprint-name').focus();
}

function closeSprintModal() {
    const modal = document.getElementById('sprint-modal');
    modal.classList.remove('active');
    currentSprintId = null;
}

async function handleSprintSubmit(e) {
    e.preventDefault();

    const id = document.getElementById('sprint-id').value;
    const data = {
        nombre: document.getElementById('sprint-name').value.trim(),
        fecha_inicio: document.getElementById('sprint-start').value,
        fecha_fin: document.getElementById('sprint-end').value
    };

    if (!data.nombre) {
        showToast('El nombre del sprint es obligatorio', 'error');
        return;
    }
    if (!data.fecha_inicio || !data.fecha_fin) {
        showToast('Las fechas de inicio y fin son obligatorias', 'error');
        return;
    }
    if (data.fecha_inicio > data.fecha_fin) {
        showToast('La fecha de inicio no puede ser posterior a la fecha de fin', 'error');
        return;
    }

    const submitBtn = document.getElementById('sprint-submit');
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;

    try {
        if (id) {
            await updateSprint(parseInt(id), data);
            showToast('Sprint actualizado correctamente', 'success');
        } else {
            await createSprint(data);
            showToast('Sprint creado correctamente', 'success');
        }
        closeSprintModal();
        await loadSprints();
    } catch (error) {
        console.error('Error saving sprint:', error);
        showToast(error.message || 'Error al guardar el sprint', 'error');
    } finally {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
    }
}

async function confirmDeleteSprint(id) {
    const sprint = currentSprints.find(s => Number(s.id) === Number(id));
    const nombre = sprint ? sprint.nombre : 'este sprint';

    if (!confirm(`¿Está seguro de eliminar el sprint "${nombre}"? Se eliminarán también todos sus items de retrospectiva.`)) {
        return;
    }

    try {
        await deleteSprint(id);
        showToast('Sprint eliminado correctamente', 'success');
        await loadSprints();
    } catch (error) {
        console.error('Error deleting sprint:', error);
        showToast(error.message || 'Error al eliminar el sprint', 'error');
    }
}

function setupForm() {
    const form = document.getElementById('sprint-form');
    if (form) form.addEventListener('submit', handleSprintSubmit);

    document.getElementById('sprint-modal')?.addEventListener('click', (e) => {
        if (e.target.id === 'sprint-modal') closeSprintModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeSprintModal();
    });
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

window.openSprintModal = openSprintModal;
window.closeSprintModal = closeSprintModal;
window.confirmDeleteSprint = confirmDeleteSprint;
