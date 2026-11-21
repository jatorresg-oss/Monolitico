/**
 * Cliente API para la aplicación de Retrospectivas Scrum.
 * Cada microservicio se ejecuta en un puerto distinto.
 */

const API_HOST = window.location.hostname || '127.0.0.1';

const API_BASE = {
    sprints: `http://${API_HOST}:3001`,
    retrospective: `http://${API_HOST}:3002`,
    feedback: `http://${API_HOST}:3003`,
    actionTracking: `http://${API_HOST}:3004`
};

const DEFAULT_HEADERS = {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
};

class ApiClient {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            ...options,
            headers: { ...DEFAULT_HEADERS, ...(options.headers || {}) }
        };

        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(url, config);

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.error || error.message || `HTTP ${response.status}`);
            }

            if (response.status === 204) {
                return null;
            }

            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                return await response.json();
            }
            return await response.text();
        } catch (error) {
            console.error(`API Error (${url}):`, error);
            throw error;
        }
    }

    get(endpoint) { return this.request(endpoint); }
    post(endpoint, data) { return this.request(endpoint, { method: 'POST', body: data }); }
    put(endpoint, data) { return this.request(endpoint, { method: 'PUT', body: data }); }
    patch(endpoint, data) { return this.request(endpoint, { method: 'PATCH', body: data }); }
    delete(endpoint) { return this.request(endpoint, { method: 'DELETE' }); }

    async healthCheck() {
        try {
            const result = await this.get('/health');
            return result.status === 'ok';
        } catch {
            return false;
        }
    }
}

export const sprintsApi = new ApiClient(API_BASE.sprints);
export const retrospectiveApi = new ApiClient(API_BASE.retrospective);
export const feedbackApi = new ApiClient(API_BASE.feedback);
export const actionTrackingApi = new ApiClient(API_BASE.actionTracking);

// ============================================================
// Sprint Service (puerto 3001)
// ============================================================
export async function getSprints() {
    return (await sprintsApi.get('/sprints')) || [];
}

export async function getSprint(id) {
    return await sprintsApi.get(`/sprints/${id}`);
}

export async function createSprint(data) {
    return await sprintsApi.post('/sprints', data);
}

export async function updateSprint(id, data) {
    return await sprintsApi.put(`/sprints/${id}`, data);
}

export async function deleteSprint(id) {
    return await sprintsApi.delete(`/sprints/${id}`);
}

// ============================================================
// Retrospective Service (puerto 3002) - items de retrospectiva
// ============================================================
export async function getAllItems() {
    return (await retrospectiveApi.get('/retro-items')) || [];
}

export async function getItemsBySprint(sprintId) {
    return (await retrospectiveApi.get(`/retro-items/sprint/${sprintId}`)) || [];
}

export async function getItemsByCategory(sprintId, categoria) {
    return (await retrospectiveApi.get(`/retro-items/sprint/${sprintId}/categoria/${categoria}`)) || [];
}

export async function getItem(id) {
    return await retrospectiveApi.get(`/retro-items/${id}`);
}

export async function createItem(data) {
    return await retrospectiveApi.post('/retro-items', data);
}

export async function updateItem(id, data) {
    return await retrospectiveApi.put(`/retro-items/${id}`, data);
}

export async function deleteItem(id) {
    return await retrospectiveApi.delete(`/retro-items/${id}`);
}

// ============================================================
// Feedback Service (puerto 3003) - consultas y resúmenes
// ============================================================
export async function getFeedbackBySprint(sprintId) {
    return (await feedbackApi.get(`/feedbacks/sprint/${sprintId}`)) || [];
}

export async function getFeedbackByType(sprintId, categoria) {
    return (await feedbackApi.get(`/feedbacks/sprint/${sprintId}/type/${categoria}`)) || [];
}

export async function getFeedbackSummary(sprintId) {
    return await feedbackApi.get(`/feedbacks/summary/${sprintId}`);
}

export async function getFeedbackStats() {
    return await feedbackApi.get('/feedbacks/stats');
}

// ============================================================
// Action Tracking Service (puerto 3004) - seguimiento de acciones
// ============================================================
export async function getActionsBySprint(sprintId) {
    return (await actionTrackingApi.get(`/action-tracking/sprint/${sprintId}`)) || [];
}

export async function getPendingActions(sprintId) {
    return (await actionTrackingApi.get(`/action-tracking/pending/${sprintId}`)) || [];
}

export async function getAction(id) {
    return await actionTrackingApi.get(`/action-tracking/${id}`);
}

export async function updateAction(id, data) {
    return await actionTrackingApi.patch(`/action-tracking/${id}`, data);
}

export async function getActionStats(sprintId = null) {
    const endpoint = sprintId ? `/action-tracking/stats/${sprintId}` : '/action-tracking/stats';
    return await actionTrackingApi.get(endpoint);
}

// ============================================================
// Estado de los servicios
// ============================================================
export async function checkAllServices() {
    const results = await Promise.allSettled([
        sprintsApi.healthCheck(),
        retrospectiveApi.healthCheck(),
        feedbackApi.healthCheck(),
        actionTrackingApi.healthCheck()
    ]);

    return {
        sprints: results[0].status === 'fulfilled' && results[0].value,
        retrospective: results[1].status === 'fulfilled' && results[1].value,
        feedback: results[2].status === 'fulfilled' && results[2].value,
        actionTracking: results[3].status === 'fulfilled' && results[3].value
    };
}

// ============================================================
// Utilidades de presentación
// ============================================================
export function formatDate(dateString) {
    if (!dateString) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        const [year, month, day] = dateString.split('-');
        return `${day}/${month}/${year}`;
    }
    const date = new Date(dateString);
    return isNaN(date) ? '' : date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

export function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return isNaN(date) ? '' : date.toLocaleDateString('es-ES', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

export const CATEGORIAS = ['logro', 'impedimento', 'accion', 'comentario', 'otro'];

const CATEGORY_META = {
    logro: { label: 'Logro', plural: 'Logros', icon: 'trophy', color: 'var(--success)' },
    impedimento: { label: 'Impedimento', plural: 'Impedimentos', icon: 'exclamation-triangle', color: 'var(--danger)' },
    accion: { label: 'Acción', plural: 'Acciones', icon: 'tasks', color: 'var(--primary)' },
    comentario: { label: 'Comentario', plural: 'Comentarios', icon: 'sticky-note', color: 'var(--info)' },
    otro: { label: 'Otro', plural: 'Otros', icon: 'circle', color: 'var(--gray-600)' }
};

export function getCategoryLabel(categoria) {
    return CATEGORY_META[categoria]?.label || categoria;
}

export function getCategoryPlural(categoria) {
    return CATEGORY_META[categoria]?.plural || categoria;
}

export function getCategoryIcon(categoria) {
    return CATEGORY_META[categoria]?.icon || 'circle';
}

export function getCategoryColor(categoria) {
    return CATEGORY_META[categoria]?.color || 'var(--gray-600)';
}

export function isCompleted(item) {
    return item.cumplida === true || item.cumplida === 1 || item.cumplida === '1';
}

export function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

export function showToast(message, type = 'info') {
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
