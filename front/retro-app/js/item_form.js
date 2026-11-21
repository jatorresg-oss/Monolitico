/**
 * Item Form Page - Clon exacto de views/item_form.php
 */

import {
    getSprints,
    getItem,
    createItem,
    updateItem,
    formatDate,
    escapeHtml,
    showToast
} from './api.js';

let currentSprintId = null;
let currentCategoria = 'logro';
let currentItemId = null;

const labels = {
    logro: 'Logro',
    impedimento: 'Impedimento',
    accion: 'Acción',
    comentario: 'Comentario'
};

const icons = {
    logro: 'trophy',
    impedimento: 'exclamation-triangle',
    accion: 'tasks',
    comentario: 'sticky-note'
};

document.addEventListener('DOMContentLoaded', async () => {
    parseURLParams();
    updateUI();
    await loadSprintInfo();
    setupForm();
    if (currentItemId) await loadItem(currentItemId);
});

function parseURLParams() {
    const params = new URLSearchParams(window.location.search);
    currentCategoria = params.get('categoria') || 'logro';
    if (!labels[currentCategoria]) currentCategoria = 'logro';
    currentSprintId = params.get('sprint_id') ? parseInt(params.get('sprint_id')) : null;
    currentItemId = params.get('id') ? parseInt(params.get('id')) : null;
}

function updateUI() {
    const badge = document.getElementById('category-badge');
    if (badge) {
        badge.className = `category-badge ${currentCategoria}`;
        badge.innerHTML = `<i class="fas fa-${icons[currentCategoria]}" aria-hidden="true"></i> ${labels[currentCategoria]}`;
    }

    const actionFields = document.getElementById('action-fields');
    if (actionFields) {
        actionFields.style.display = currentCategoria === 'accion' ? 'block' : 'none';
    }

    const isEdit = currentItemId !== null;
    document.getElementById('page-title').textContent = `${isEdit ? 'Modificar' : 'Agregar'} Item - ${labels[currentCategoria]}`;
    document.getElementById('header-title').innerHTML = `<i class="fas fa-${isEdit ? 'edit' : 'plus'}" aria-hidden="true"></i> ${isEdit ? 'Modificar' : 'Agregar'} Item - ${labels[currentCategoria]}`;
    document.getElementById('panel-title').innerHTML = `<i class="fas fa-${isEdit ? 'edit' : 'plus'}" aria-hidden="true"></i> ${isEdit ? 'Modificar' : 'Agregar'} Item`;
    document.getElementById('submit-btn').innerHTML = `<i class="fas fa-save" aria-hidden="true"></i> ${isEdit ? 'Modificar' : 'Guardar'}`;

    const cancelBtn = document.getElementById('cancel-btn');
    if (cancelBtn) {
        cancelBtn.href = currentSprintId ? `retrospectiva.html?sprint_id=${currentSprintId}` : 'retrospectiva.html';
    }

    const backToRetro = document.getElementById('back-to-retro');
    if (backToRetro && currentSprintId) {
        backToRetro.href = `retrospectiva.html?sprint_id=${currentSprintId}`;
    }
}

async function loadSprintInfo() {
    if (!currentSprintId) return;

    try {
        const sprint = await getSprints().then(sprints => sprints.find(s => s.id === currentSprintId));
        if (sprint) {
            document.getElementById('page-subtitle').textContent = `Sprint: ${sprint.nombre}`;
        }
    } catch (e) {
        console.error('Error loading sprint:', e);
    }
}

function setupForm() {
    const form = document.getElementById('item-form');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const descripcion = document.getElementById('descripcion').value.trim();
        if (!descripcion) {
            showToast('La descripción es obligatoria', 'error');
            document.getElementById('descripcion').focus();
            return;
        }
        if (!currentSprintId) {
            showToast('No se indicó el sprint. Vuelva a la retrospectiva.', 'error');
            return;
        }

        const data = {
            sprint_id: currentSprintId,
            categoria: currentCategoria,
            descripcion
        };

        if (currentCategoria === 'accion') {
            data.cumplida = document.getElementById('cumplida').checked;
            const fecha = document.getElementById('fecha_revision').value;
            data.fecha_revision = fecha || null;
        }

        const submitBtn = document.getElementById('submit-btn');
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;

        try {
            if (currentItemId) {
                await updateItem(currentItemId, data);
                showToast('Item actualizado correctamente', 'success');
            } else {
                await createItem(data);
                showToast('Item creado correctamente', 'success');
            }
            window.location.href = `retrospectiva.html?sprint_id=${currentSprintId}`;
        } catch (error) {
            console.error('Error saving item:', error);
            showToast(error.message || 'Error al guardar el item', 'error');
        } finally {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    });

    const textarea = document.getElementById('descripcion');
    textarea.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
}

async function loadItem(id) {
    try {
        const item = await getItem(id);
        document.getElementById('descripcion').value = item.descripcion || '';
        currentCategoria = labels[item.categoria] ? item.categoria : 'otro';
        currentSprintId = item.sprint_id || currentSprintId;

        if (currentCategoria === 'accion') {
            document.getElementById('cumplida').checked = item.cumplida === true || item.cumplida === 1;
            document.getElementById('fecha_revision').value = item.fecha_revision || '';
        }

        updateUI();
        await loadSprintInfo();
    } catch (e) {
        console.error('Error loading item:', e);
        showToast('Error al cargar el item', 'error');
    }
}

window.showToast = showToast;