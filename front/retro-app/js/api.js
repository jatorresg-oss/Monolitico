/**
 * API Client for Retrospectivas Microservices
 * Base URLs for each microservice
 */

const API_BASE = {
    sprints: 'http://localhost:3001',
    retrospectives: 'http://localhost:3002',
    feedback: 'http://localhost:3003',
    actionTracking: 'http://localhost:3004'
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
            headers: { ...DEFAULT_HEADERS, ...options.headers },
            ...options
        };

        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                const error = await response.json().catch(() => ({ message: 'Error en la petición' }));
                throw new Error(error.message || `HTTP ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            return await response.text();
        } catch (error) {
            console.error(`API Error (${this.baseUrl}${endpoint}):`, error);
            throw error;
        }
    }

    get(endpoint) { return this.request(endpoint, { method: 'GET' }); }
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

// Instancias para cada servicio
export const sprintsApi = new ApiClient(API_BASE.sprints);
export const retrospectivesApi = new ApiClient(API_BASE.retrospectives);
export const feedbackApi = new ApiClient(API_BASE.feedback);
export const actionTrackingApi = new ApiClient(API_BASE.actionTracking);

// ============================================================
// Sprint Service Methods
// ============================================================
export async function getSprints() {
    return await sprintsApi.get('/sprints');
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
// Retrospective Service Methods
// ============================================================
export async function getRetrospectives() {
    return await retrospectivesApi.get('/retrospectives');
}

export async function getRetrospectiveBySprint(sprintId) {
    return await retrospectivesApi.get(`/retrospectives/sprint/${sprintId}`);
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
// Feedback Service Methods
// ============================================================
export async function getFeedbackByRetrospective(retroId) {
    return await feedbackApi.get(`/feedbacks/retrospective/${retroId}`);
}

export async function getFeedbackByType(retroId, type) {
    return await feedbackApi.get(`/feedbacks/retrospective/${retroId}/type/${type}`);
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

// ============================================================
// Action Tracking Service Methods
// ============================================================
export async function getPendingActions(sprintId) {
    return await actionTrackingApi.get(`/action-tracking/pending/${sprintId}`);
}

export async function getActionTrackingBySprint(sprintId) {
    return await actionTrackingApi.get(`/action-tracking/sprint/${sprintId}`);
}

export async function getActionTrackingHistory(feedbackId) {
    return await actionTrackingApi.get(`/action-tracking/history/${feedbackId}`);
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
// Health Checks
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
// Utility Functions
// ============================================================
export function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

export function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export function getTypeLabel(type) {
    const labels = {
        'positive': 'Logro',
        'negative': 'Impedimento',
        'action': 'Acción',
        'comment': 'Comentario'
    };
    return labels[type] || type;
}

export function getTypeIcon(type) {
    const icons = {
        'positive': 'trophy',
        'negative': 'exclamation-triangle',
        'action': 'tasks',
        'comment': 'sticky-note'
    };
    return icons[type] || 'circle';
}

export function getTypeColor(type) {
    const colors = {
        'positive': 'var(--success)',
        'negative': 'var(--danger)',
        'action': 'var(--primary)',
        'comment': 'var(--info)'
    };
    return colors[type] || 'var(--gray-500)';
}

export function getPriorityLabel(priority) {
    const labels = {
        'low': 'Baja',
        'medium': 'Media',
        'high': 'Alta',
        'critical': 'Crítica'
    };
    return labels[priority] || priority;
}

export function getStatusLabel(status) {
    const labels = {
        'completed': 'Cumplida',
        'partial': 'Parcial',
        'not_completed': 'No cumplida',
        'carried_over': 'Trasladada'
    };
    return labels[status] || status;
}