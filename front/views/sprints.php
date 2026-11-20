<?php
require __DIR__ . '/layout/components.php';

$apiBaseUrl = '/back/usuarios_ms/public';

// Obtener sprints desde la API
$sprints = [];
$stats = [];

try {
    $response = file_get_contents($apiBaseUrl . '/sprints');
    if ($response !== false) {
        $data = json_decode($response, true);
        if (is_array($data)) {
            $sprints = $data;
            
            // Obtener stats para cada sprint
            foreach ($sprints as $sprint) {
                $itemResponse = file_get_contents($apiBaseUrl . "/feedbacks/retrospective/sprint/{$sprint['id']}");
                if ($itemResponse !== false) {
                    $items = json_decode($itemResponse, true);
                    $sprintStats = ['logro' => 0, 'impedimento' => 0, 'accion' => 0, 'comentario' => 0];
                    foreach ($items as $item) {
                        $cat = $item['type'] === 'positive' ? 'logro' : 
                               ($item['type'] === 'negative' ? 'impedimento' : 
                               ($item['type'] === 'action' ? 'accion' : 'comentario'));
                        if (isset($sprintStats[$cat])) $sprintStats[$cat]++;
                    }
                    $stats[$sprint['id']] = $sprintStats;
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching sprints: " . $e->getMessage());
}

$pageTitle = 'Gestión de Sprints';
$pageDescription = 'Registro de Retrospectivas Ágiles';
$activeTab = 'sprints';

ob_start();
?>
<?php echo renderPanelHeader(
    'Lista de Sprints',
    'calendar-alt',
    [
        ['href' => 'sprint_form.php', 'class' => 'btn-primary', 'icon' => 'plus', 'label' => 'Nuevo Sprint', 'aria-label' => 'Crear nuevo sprint']
    ]
); ?>

<div class="sprints-list" id="sprints-list" role="list" aria-label="Lista de sprints">
    <?php if (empty($sprints)): ?>
        <?php echo renderEmptyState(
            'calendar-plus',
            'No hay sprints registrados',
            'Crea tu primer sprint para empezar a registrar retrospectivas',
            '<a href="sprint_form.php" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> Crear primer sprint</a>'
        ); ?>
    <?php else: ?>
        <?php foreach ($sprints as $sprint): ?>
            <?php echo renderSprintCard($sprint, $stats[$sprint['id']] ?? [], true); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
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