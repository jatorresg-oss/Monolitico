<?php
/**
 * Base Layout Template
 * Usage: 
 *   $pageTitle = 'Mi Página';
 *   $pageDescription = 'Descripción opcional';
 *   $activeTab = 'sprints'; // 'sprints' | 'retrospectiva' | 'historial'
 *   $extraHead = '<link rel="stylesheet" href="...">'; // opcional
 *   ob_start();
 *   // ... contenido de la vista ...
 *   $content = ob_get_clean();
 *   require __DIR__ . '/layout/base.php';
 */

$pageTitle = $pageTitle ?? 'Retrospectivas';
$pageDescription = $pageDescription ?? '';
$activeTab = $activeTab ?? 'sprints';
$extraHead = $extraHead ?? '';
$showToastContainer = $showToastContainer ?? true;
$bodyClass = $bodyClass ?? '';
$currentSprintId = $currentSprintId ?? null;

// URL base de la API
$apiBaseUrl = '/back/usuarios_ms/public';

// Helpers para navegación
$navTabs = [
    'sprints' => [
        'href' => 'sprints.php',
        'icon' => 'fa-calendar-alt',
        'label' => 'Sprints',
        'aria-label' => 'Gestión de Sprints'
    ],
    'retrospectiva' => [
        'href' => $currentSprintId ? "retrospectiva.php?sprint_id={$currentSprintId}" : 'retrospectiva.php',
        'icon' => 'fa-comments',
        'label' => 'Retrospectiva',
        'aria-label' => 'Ver retrospectiva del sprint'
    ],
    'historial' => [
        'href' => 'historial.php',
        'icon' => 'fa-history',
        'label' => 'Historial',
        'aria-label' => 'Historial de retrospectivas'
    ]
];

function renderNavTabs(array $tabs, string $active): string {
    $html = '<nav class="nav-tabs" role="tablist" aria-label="Navegación principal">';
    foreach ($tabs as $key => $tab) {
        $isActive = $key === $active;
        $html .= sprintf(
            '<button class="tab-btn%s" role="tab" aria-selected="%s" aria-controls="panel-%s" onclick="location.href=\'%s\'" aria-label="%s">',
            $isActive ? ' active' : '',
            $isActive ? 'true' : 'false',
            $key,
            $tab['href'],
            $tab['aria-label']
        );
        $html .= sprintf('<i class="fas %s" aria-hidden="true"></i> %s', $tab['icon'], $tab['label']);
        $html .= '</button>';
    }
    $html .= '</nav>';
    return $html;
}

function renderToastContainer(): string {
    return '<div class="toast-container" id="toast-container" aria-live="polite" aria-atomic="true"></div>';
}

function renderFlashMessages(): string {
    $messages = [];
    if (!empty($_GET['success'])) $messages[] = ['type' => 'success', 'message' => 'Operación realizada correctamente'];
    if (!empty($_GET['error'])) $messages[] = ['type' => 'error', 'message' => 'Error al procesar la solicitud'];
    if (!empty($_GET['deleted'])) $messages[] = ['type' => 'success', 'message' => 'Elemento eliminado correctamente'];
    if (!empty($_GET['warning'])) $messages[] = ['type' => 'warning', 'message' => $_GET['warning']];
    if (!empty($_GET['info'])) $messages[] = ['type' => 'info', 'message' => $_GET['info']];
    
    if (empty($messages)) return '';
    
    $html = '<script>document.addEventListener("DOMContentLoaded", function() {';
    foreach ($messages as $msg) {
        $html .= sprintf(
            'showToast("%s", "%s");',
            addslashes($msg['message']),
            $msg['type']
        );
    }
    $html .= '});</script>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <link rel="stylesheet" href="../public/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php echo $extraHead; ?>
</head>

<body class="<?php echo $bodyClass; ?>">
    <?php if ($showToastContainer): ?>
        <?php echo renderToastContainer(); ?>
        <?php echo renderFlashMessages(); ?>
    <?php endif; ?>

    <div class="app-container">
        <header class="header" role="banner">
            <div class="header-content">
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                <?php if ($pageDescription): ?>
                    <p class="subtitle"><?php echo htmlspecialchars($pageDescription); ?></p>
                <?php endif; ?>
            </div>
        </header>

        <?php echo renderNavTabs($navTabs, $activeTab); ?>

        <main class="main-content" role="main" id="main-content">
            <?php echo $content; ?>
        </main>
    </div>

    <script>
        // API Base URL
        const API_BASE = '<?php echo $apiBaseUrl; ?>';
        
        // Toast System
        function showToast(message, type = 'info', duration = 4000) {
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
            
            setTimeout(() => {
                if (toast.parentNode) toast.remove();
            }, duration);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Clean URL params on load
        (function() {
            const params = new URLSearchParams(window.location.search);
            if (params.has('success') || params.has('error') || params.has('deleted') || params.has('warning') || params.has('info')) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, '', cleanUrl);
            }
        })();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + S = Sprints, Alt + R = Retrospectiva, Alt + H = Historial
            if (e.altKey && !e.ctrlKey && !e.metaKey) {
                if (e.key === 's') { e.preventDefault(); window.location.href = 'sprints.php'; }
                if (e.key === 'r') { e.preventDefault(); window.location.href = 'retrospectiva.php<?php echo $currentSprintId ? "?sprint_id={$currentSprintId}" : ""; ?>'; }
                if (e.key === 'h') { e.preventDefault(); window.location.href = 'historial.php'; }
            }
            // Escape = cerrar modales/toasts
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active, .toast').forEach(el => el.remove());
            }
        });
        
        // Confirm dialog helper
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
        
        // API helper functions
        async function apiGet(endpoint) {
            const response = await fetch(API_BASE + endpoint);
            if (!response.ok) throw new Error('Error en la petición');
            return response.json();
        }
        
        async function apiPost(endpoint, data) {
            const response = await fetch(API_BASE + endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error('Error en la petición');
            return response.json();
        }
        
        async function apiPut(endpoint, data) {
            const response = await fetch(API_BASE + endpoint, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error('Error en la petición');
            return response.json();
        }
        
        async function apiDelete(endpoint) {
            const response = await fetch(API_BASE + endpoint, {
                method: 'DELETE'
            });
            if (!response.ok) throw new Error('Error en la petición');
            return response.json();
        }
    </script>
</body>
</html>