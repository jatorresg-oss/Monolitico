/**
 * Cliente API para la aplicación de Retrospectivas Scrum.
 * Cada microservicio se ejecuta en un puerto distinto.
 */

const API_HOST = '127.0.0.1';

const API_BASE = {
    sprints: `http://${API_HOST}:3001`,
    retrospectives: `http://${API_HOST}:3002`,
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
                const apiError = new Error(error.error || error.message || `HTTP ${response.status}`);
                apiError.status = response.status;
                throw apiError;
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
export const retrospectivesApi = new ApiClient(API_BASE.retrospectives);
export const feedbackApi = new ApiClient(API_BASE.feedback);
export const actionTrackingApi = new ApiClient(API_BASE.actionTracking);

// ============================================================
// Sprint Service (puerto 3001)
// ============================================================
export async function getSprints() {
    const sprints = (await sprintsApi.get('/sprints')) || [];
    return sprints.map(sprint => ({
        ...sprint,
        name: sprint.name ?? sprint.nombre,
        nombre: sprint.nombre ?? sprint.name,
        start_date: sprint.start_date ?? sprint.fecha_inicio,
        end_date: sprint.end_date ?? sprint.fecha_fin,
        status: sprint.status ?? 'planned'
    }));
}

export async function getSprint(id) {
    return await sprintsApi.get(`/sprints/${id}`);
}

export async function createSprint(data) {
    return await sprintsApi.post('/sprints', {
        nombre: data.nombre ?? data.name,
        fecha_inicio: data.fecha_inicio ?? data.start_date,
        fecha_fin: data.fecha_fin ?? data.end_date
    });
}

export async function updateSprint(id, data) {
    const payload = {};
    if ('nombre' in data || 'name' in data) payload.nombre = data.nombre ?? data.name;
    if ('fecha_inicio' in data || 'start_date' in data) payload.fecha_inicio = data.fecha_inicio ?? data.start_date;
    if ('fecha_fin' in data || 'end_date' in data) payload.fecha_fin = data.fecha_fin ?? data.end_date;
    return await sprintsApi.put(`/sprints/${id}`, payload);
}

export async function deleteSprint(id) {
    return await sprintsApi.delete(`/sprints/${id}`);
}

// ============================================================
// Retrospective Service (puerto 3002) - retrospectivas
// ============================================================
export async function getRetrospectives() {
    return (await retrospectivesApi.get('/retrospectives')) || [];
}

export async function getRetrospectiveBySprint(sprintId) {
    try {
        return await retrospectivesApi.get(`/retrospectives/sprint/${sprintId}`);
    } catch (e) {
        if (e.status === 404) return null;
        throw e;
    }
}

export async function getRetrospective(id) {
    return await retrospectivesApi.get(`/retrospectives/${id}`);
}

export async function createRetrospective(data) {
    return await retrospectivesApi.post('/retrospectives', data);
}

export async function updateRetrospective(id, data) {
    return await retrospectivesApi.put(`/retrospectives/${id}`, data);
}

export async function completeRetrospective(id) {
    return await retrospectivesApi.put(`/retrospectives/${id}/complete`);
}

export async function deleteRetrospective(id) {
    return await retrospectivesApi.delete(`/retrospectives/${id}`);
}

// ============================================================
// Feedback Service (puerto 3003) - items de retrospectiva
// ============================================================
export async function getFeedbackByRetrospective(retroId) {
    return (await feedbackApi.get(`/feedbacks/retrospective/${retroId}`)) || [];
}

export async function getFeedbackByType(retroId, type) {
    return (await feedbackApi.get(`/feedbacks/retrospective/${retroId}/type/${type}`)) || [];
}

export async function getFeedback(id) {
    return await feedbackApi.get(`/feedbacks/${id}`);
}

export async function createFeedback(data) {
    return await feedbackApi.post('/feedbacks', data);
}

export async function createFeedbackBulk(data) {
    return await feedbackApi.post('/feedbacks/bulk', data);
}

export async function updateFeedback(id, data) {
    return await feedbackApi.put(`/feedbacks/${id}`, data);
}

export async function deleteFeedback(id) {
    return await feedbackApi.delete(`/feedbacks/${id}`);
}

export async function getItem(id) {
    return await feedbackApi.get(`/feedbacks/${id}`);
}

export async function createItem(data) {
    return await feedbackApi.post('/feedbacks', data);
}

export async function updateItem(id, data) {
    return await feedbackApi.put(`/feedbacks/${id}`, data);
}

// ============================================================
// Action Tracking Service (puerto 3004) - seguimiento de acciones
// ============================================================
export async function getPendingActions(sprintId) {
    return (await actionTrackingApi.get(`/action-tracking/pending/${sprintId}`)) || [];
}

export async function getActionTrackingBySprint(sprintId) {
    return (await actionTrackingApi.get(`/action-tracking/sprint/${sprintId}`)) || [];
}

export async function getActionTrackingHistory(feedbackId) {
    return (await actionTrackingApi.get(`/action-tracking/history/${feedbackId}`)) || [];
}

export async function createActionTracking(data) {
    return await actionTrackingApi.post('/action-tracking', data);
}

export async function updateActionTracking(id, data) {
    return await actionTrackingApi.put(`/action-tracking/${id}`, data);
}

export async function deleteActionTracking(id) {
    return await actionTrackingApi.delete(`/action-tracking/${id}`);
}

// ============================================================
// Estado de los servicios
// ============================================================
export async function checkAllServices() {
    const results = await Promise.allSettled([
        sprintsApi.healthCheck(),
        retrospectivesApi.healthCheck(),
        feedbackApi.healthCheck(),
        actionTrackingApi.healthCheck()
    ]);

    return {
        sprints: results[0].status === 'fulfilled' && results[0].value,
        retrospectives: results[1].status === 'fulfilled' && results[1].value,
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

export function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value ?? '';
    return element.innerHTML;
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