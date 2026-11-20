<?php
require __DIR__ . '/layout/components.php';

$apiBaseUrl = '/back/usuarios_ms/public';

$sprints = [];
$items = [];
$acciones_anteriores = [];
$current_sprint_id = isset($_GET['sprint_id']) ? (int)$_GET['sprint_id'] : null;

// Obtener lista de sprints
try {
    $response = file_get_contents($apiBaseUrl . '/sprints');
    if ($response !== false) {
        $sprints = json_decode($response, true) ?? [];
    }
} catch (Exception $e) {
    error_log("Error fetching sprints: " . $e->getMessage());
}

if ($current_sprint_id) {
    // Obtener retrospectiva del sprint
    try {
        $response = file_get_contents($apiBaseUrl . "/retrospectives/sprint/{$current_sprint_id}");
        if ($response !== false) {
            $retro = json_decode($response, true);
            if ($retro && isset($retro['id'])) {
                // Obtener items de la retrospectiva
                $itemResponse = file_get_contents($apiBaseUrl . "/feedbacks/retrospective/{$retro['id']}");
                if ($itemResponse !== false) {
                    $items = json_decode($itemResponse, true) ?? [];
                }
                
                // Obtener acciones anteriores (acciones de sprints anteriores no revisadas)
                $actionResponse = file_get_contents($apiBaseUrl . "/action-tracking/pending/{$current_sprint_id}");
                if ($actionResponse !== false) {
                    $acciones_anteriores = json_decode($actionResponse, true) ?? [];
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching retrospective: " . $e->getMessage());
    }
}

// Group items by category - adaptar tipos de la API a categorías de la UI
$items_by_category = [
    'logro' => [],
    'impedimento' => [],
    'accion' => [],
    'comentario' => [],
];

foreach ($items as $item) {
    // Mapear tipos de API a categorías UI
    $cat = $item['type'] === 'positive' ? 'logro' : 
           ($item['type'] === 'negative' ? 'impedimento' : 
           ($item['type'] === 'action' ? 'accion' : 'comentario'));
    if (isset($items_by_category[$cat])) {
        // Agregar campos necesarios para la vista
        $item['categoria'] = $cat;
        $items_by_category[$cat][] = $item;
    }
}

$categoryConfig = [
    'logro' => ['icon' => 'trophy', 'title' => 'Logros (¿Qué funcionó bien?)', 'color' => 'success', 'addLabel' => 'Agregar logro'],
    'impedimento' => ['icon' => 'exclamation-triangle', 'title' => 'Impedimentos (¿Qué necesita mejorar?)', 'color' => 'danger', 'addLabel' => 'Agregar impedimento'],
    'accion' => ['icon' => 'tasks', 'title' => 'Acciones (Compromisos para el próximo sprint)', 'color' => 'primary', 'addLabel' => 'Agregar acción'],
    'comentario' => ['icon' => 'sticky-note', 'title' => 'Comentarios Adicionales', 'color' => 'info', 'addLabel' => 'Agregar comentario']
];

$pageTitle = 'Nueva Retrospectiva';
$pageDescription = 'Mejora continua para equipos Scrum';
$activeTab = 'retrospectiva';

ob_start();
?>
<?php echo renderPanelHeader('Registrar Retrospectiva', 'comments'); ?>

<!-- Sprint Selector -->
<?php echo renderSprintSelector($sprints, $current_sprint_id, 'Seleccionar Sprint:'); ?>

<!-- Acciones Anteriores -->
<?php if (!empty($acciones_anteriores)): ?>
    <?php echo renderPreviousActions($acciones_anteriores, $current_sprint_id); ?>
<?php endif; ?>

<?php if ($current_sprint_id): ?>
    <form action="operaciones/retro_items/registrar_item.php" method="post" class="retro-form" id="form-retrospectiva">
        <input type="hidden" name="sprint_id" value="<?php echo $current_sprint_id; ?>">
        
        <?php foreach ($categoryConfig as $cat => $config): ?>
            <section class="retro-section" aria-labelledby="section-<?php echo $cat; ?>">
                <?php 
                $addUrl = "item_form.php?categoria={$cat}&sprint_id={$current_sprint_id}";
                echo renderSectionHeader(
                    $config['icon'], 
                    $config['title'],
                    '<a href="' . $addUrl . '" class="btn btn-sm btn-add" aria-label="' . $config['addLabel'] . '"><i class="fas fa-plus" aria-hidden="true"></i> ' . $config['addLabel'] . '</a>',
                    [$cat]
                ); 
                ?>
                <div class="items-container" id="<?php echo $cat; ?>-container">
                    <?php if (empty($items_by_category[$cat])): ?>
                        <div class="empty-state" style="padding: 30px 16px; border: 2px dashed var(--gray-300); border-radius: var(--radius);">
                            <i class="fas fa-<?php echo $config['icon']; ?>" aria-hidden="true" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.3;"></i>
                            <p style="font-size: 0.9rem;">No hay <?php echo strtolower($config['title']); ?> registrados</p>
                            <a href="<?php echo $addUrl; ?>" class="btn btn-sm btn-add" style="margin-top: 8px;"><i class="fas fa-plus" aria-hidden="true"></i> Agregar primero</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($items_by_category[$cat] as $item): ?>
                            <?php echo renderRetroItem($item, $current_sprint_id, true); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </form>
<?php else: ?>
    <?php echo renderEmptyState(
        'info-circle',
        'Seleccione un sprint',
        'Elija un sprint del desplegable para comenzar a registrar la retrospectiva',
        '<a href="sprints.php" class="btn btn-primary" style="margin-top: 16px;"><i class="fas fa-plus" aria-hidden="true"></i> Crear nuevo sprint</a>'
    ); ?>
<?php endif; ?>

<script>
// Auto-focus sprint selector if no sprint selected
<?php if (!$current_sprint_id): ?>
document.getElementById('select-sprint').focus();
<?php endif; ?>

// Enhanced delete confirmation
document.querySelectorAll('.item-actions .btn-danger, .item-actions .btn-icon[title="Eliminar"]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('¿Está seguro de eliminar este item?')) {
            e.preventDefault();
        }
    });
});

// Keyboard shortcut hint
const hint = document.createElement('div');
hint.className = 'keyboard-hint';
hint.innerHTML = 'Atajos: <kbd>Alt</kbd>+<kbd>S</kbd> Sprints · <kbd>Alt</kbd>+<kbd>R</kbd> Retrospectiva · <kbd>Alt</kbd>+<kbd>H</kbd> Historial';
document.body.appendChild(hint);
setTimeout(() => hint.classList.add('visible'), 2000);
setTimeout(() => hint.classList.remove('visible'), 8000);
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout/base.php';