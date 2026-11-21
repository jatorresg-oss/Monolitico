/**
 * Sprints Management Page
 */

import { 
    getSprints, 
    createSprint, 
    updateSprint, 
    deleteSprint,
    checkAllServices,
    formatDate,
    showToast 
} from './api.js';

let currentSprints = [];

document.addEventListener('DOMContentLoaded', async () => {
    await checkServicesStatus();
    await loadSprints();
    setupForm();
});

async function checkServicesStatus() {
    const status = await checkAllServices();
    const container = document.getElementById('services-status');
    if (container) {
        container.innerHTML = `
            <div class="service-badge ${status.sprints ? 'online' : 'offline'}">
                <i class="fas fa-calendar-alt"></i> Sprint Service ${status.sprints ? '●' : '○'}
            </div>
        `;
    }
}

async function loadSprints() {
    try {
        showLoading(true);
        currentSprints = await getSprints() || [];
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

    container.innerHTML = currentSprints.map(sprint => `
        <article class="sprint-card" data-sprint-id="${sprint.id}">
            <div class="sprint-card-header">
                <h3>${escapeHtml(sprint.name)}</h3>
                <div class="sprint-card-actions">
                    <a href="retrospectiva.html?sprint_id=${sprint.id}" class="btn btn-sm btn-add">
                        <i class="fas fa-comments"></i> Retrospectiva
                    </a>
                    <button class="btn btn-secondary btn-sm" onclick="openSprintModal(${sprint.id})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDeleteSprint(${sprint.id}, '${escapeHtml(sprint.name)}')">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
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

// Modal Management
let currentSprintId = null;

function openSprintModal(id = null) {
    currentSprintId = id;
    const modal = document.getElementById('sprint-modal');
    const form = document.getElementById('sprint-form');
    const title = document.getElementById('modal-title');

    form.reset();
    document.getElementById('sprint-id').value = '';

    if (id) {
        title.innerHTML = '<i class="fas fa-edit"></i> Modificar Sprint';
        const sprint = currentSprints.find(s => s.id === id);
        if (sprint) {
            document.getElementById('sprint-id').value = sprint.id;
            document.getElementById('sprint-name').value = sprint.name;
            document.getElementById('sprint-start').value = sprint.start_date;
            document.getElementById('sprint-end').value = sprint.end_date;
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
        team_id: 1, // Default team for now
        name: document.getElementById('sprint-name').value.trim(),
        start_date: document.getElementById('sprint-start').value,
        end_date: document.getElementById('sprint-end').value,
        status: 'planned'
    };

    const start = data.start_date;
    const end = data.end_date;
    if (start && end && start > end) {
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
        showToast('Error al guardar el sprint', 'error');
    } finally {
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
    }
}

async function confirmDeleteSprint(id, name) {
    if (!confirm(`¿Está seguro de eliminar el sprint "${name}"? Se eliminarán también todos sus items de retrospectiva.`)) {
        return;
    }

    try {
        await deleteSprint(id);
        showToast('Sprint eliminado correctamente', 'success');
        await loadSprints();
    } catch (error) {
        console.error('Error deleting sprint:', error);
        showToast('Error al eliminar el sprint', 'error');
    }
}

function setupForm() {
    const form = document.getElementById('sprint-form');
    if (form) {
        form.addEventListener('submit', handleSprintSubmit);
    }

    // Close modal on overlay click
    document.getElementById('sprint-modal')?.addEventListener('click', (e) => {
        if (e.target.id === 'sprint-modal') closeSprintModal();
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

// Global functions for inline onclick
window.openSprintModal = openSprintModal;
window.closeSprintModal = closeSprintModal;
window.confirmDeleteSprint = confirmDeleteSprint;
window.showToast = showToast;