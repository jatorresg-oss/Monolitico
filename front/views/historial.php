<?php
require __DIR__ . '/layout/components.php';

$apiBaseUrl = '/back/usuarios_ms/public';

$sprints = [];
$all_items = [];
$filter_sprint_id = isset($_GET['sprint_id']) ? (int)$_GET['sprint_id'] : null;

// Obtener lista de sprints
try {
    $response = file_get_contents($apiBaseUrl . '/sprints');
    if ($response !== false) {
        $sprints = json_decode($response, true) ?? [];
    }
} catch (Exception $e) {
    error_log("Error fetching sprints: " . $e->getMessage());
}

if ($filter_sprint_id) {
    // Filtrar por sprint específico
    try {
        $response = file_get_contents($apiBaseUrl . "/retrospectives/sprint/{$filter_sprint_id}");
        if ($response !== false) {
            $retro = json_decode($response, true);
            if ($retro && isset($retro['id'])) {
                $itemResponse = file_get_contents($apiBaseUrl . "/feedbacks/retrospective/{$retro['id']}");
                if ($itemResponse !== false) {
                    $items = json_decode($itemResponse, true) ?? [];
                    // Enriquecer items con info del sprint
                    foreach ($items as $item) {
                        $item['sprint_nombre'] = $retro['sprint']['nombre'] ?? '';
                        $item['sprint_fecha_inicio'] = $retro['sprint']['fecha_inicio'] ?? '';
                        $item['sprint_fecha_fin'] = $retro['sprint']['fecha_fin'] ?? '';
                        // Mapear tipo a categoría
                        $item['categoria'] = $item['type'] === 'positive' ? 'logro' : 
                                           ($item['type'] === 'negative' ? 'impedimento' : 
                                           ($item['type'] === 'action' ? 'accion' : 'comentario'));
                    }
                    $all_items = [$filter_sprint_id => $items];
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching sprint items: " . $e->getMessage());
    }
} else {
    // Obtener todos los items de todos los sprints
    foreach ($sprints as $sprint) {
        try {
            $response = file_get_contents($apiBaseUrl . "/retrospectives/sprint/{$sprint['id']}");
            if ($response !== false) {
                $retro = json_decode($response, true);
                if ($retro && isset($retro['id'])) {
                    $itemResponse = file_get_contents($apiBaseUrl . "/feedbacks/retrospective/{$retro['id']}");
                    if ($itemResponse !== false) {
                        $items = json_decode($itemResponse, true) ?? [];
                        foreach ($items as $item) {
                            $item['sprint_nombre'] = $sprint['nombre'];
                            $item['sprint_fecha_inicio'] = $sprint['fecha_inicio'];
                            $item['sprint_fecha_fin'] = $sprint['fecha_fin'];
                            $item['categoria'] = $item['type'] === 'positive' ? 'logro' : 
                                               ($item['type'] === 'negative' ? 'impedimento' : 
                                               ($item['type'] === 'action' ? 'accion' : 'comentario'));
                        }
                        if (!empty($items)) {
                            $all_items[$sprint['id']] = $items;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching items for sprint {$sprint['id']}: " . $e->getMessage());
        }
    }
}

// Sort sprints by date descending
$sprint_ids = array_keys($all_items);
usort($sprint_ids, function($a, $b) use ($all_items) {
    $date_a = $all_items[$a][0]['sprint_fecha_inicio'] ?? '';
    $date_b = $all_items[$b][0]['sprint_fecha_inicio'] ?? '';
    return strcmp($date_b, $date_a);
});

$categoryOrder = ['logro', 'impedimento', 'accion', 'comentario'];
$categoryLabels = [
    'logro' => 'Logros',
    'impedimento' => 'Impedimentos',
    'accion' => 'Acciones',
    'comentario' => 'Comentarios'
];
$categoryIcons = [
    'logro' => 'trophy',
    'impedimento' => 'exclamation-triangle',
    'accion' => 'tasks',
    'comentario' => 'sticky-note'
];

$pageTitle = 'Historial de Retrospectivas';
$pageDescription = 'Seguimiento de la mejora continua';
$activeTab = 'historial';

ob_start();
?>
<?php echo renderPanelHeader('Historial de Retrospectivas', 'history'); ?>

<!-- Filtro de Sprint -->
<div class="historial-filters">
    <form method="get" action="historial.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: end;">
        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 250px;">
            <label for="filter-sprint">Filtrar por Sprint</label>
            <select id="filter-sprint" name="sprint_id" class="form-control" onchange="this.form.submit()" aria-label="Filtrar historial por sprint">
                <option value="">-- Todos los sprints --</option>
                <?php foreach ($sprints as $sprint): ?>
                    <option value="<?php echo $sprint['id']; ?>" <?php echo $filter_sprint_id == $sprint['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sprint['nombre']); ?> 
                        (<?php echo date('d/m/Y', strtotime($sprint['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($sprint['fecha_fin'])); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($filter_sprint_id): ?>
            <a href="historial.php" class="btn btn-secondary" style="height: fit-content;"><i class="fas fa-times" aria-hidden="true"></i> Limpiar filtro</a>
        <?php endif; ?>
    </form>
</div>

<div class="historial-content" id="historial-content">
    <?php if (empty($all_items)): ?>
        <?php echo renderEmptyState(
            'history',
            'No hay retrospectivas registradas',
            $filter_sprint_id ? 'Este sprint no tiene items de retrospectiva' : 'Crea un sprint y registra tu primera retrospectiva',
            !$filter_sprint_id ? '<a href="sprints.php" class="btn btn-primary" style="margin-top: 16px;"><i class="fas fa-plus" aria-hidden="true"></i> Crear primer sprint</a>' : ''
        ); ?>
    <?php else: ?>
        <?php foreach ($sprint_ids as $sprint_id): 
            $items = $all_items[$sprint_id];
            $sprint_nombre = $items[0]['sprint_nombre'] ?? '';
            $sprint_fecha_inicio = $items[0]['sprint_fecha_inicio'] ?? '';
            $sprint_fecha_fin = $items[0]['sprint_fecha_fin'] ?? '';
            
            // Count items per category for this sprint
            $catCounts = ['logro' => 0, 'impedimento' => 0, 'accion' => 0, 'comentario' => 0];
            foreach ($items as $item) {
                $cat = $item['categoria'] ?? 'comentario';
                if (isset($catCounts[$cat])) $catCounts[$cat]++;
            }
            $totalItems = count($items);
        ?>
            <article class="historial-sprint" aria-labelledby="sprint-title-<?php echo $sprint_id; ?>">
                <header class="historial-sprint-header">
                    <div>
                        <h3 id="sprint-title-<?php echo $sprint_id; ?>"><?php echo htmlspecialchars($sprint_nombre); ?></h3>
                        <div class="historial-sprint-dates">
                            <i class="fas fa-calendar-alt" aria-hidden="true"></i> 
                            <?php echo date('d/m/Y', strtotime($sprint_fecha_inicio)); ?> - 
                            <?php echo date('d/m/Y', strtotime($sprint_fecha_fin)); ?>
                            <span style="margin-left: 16px; color: var(--gray-500); font-size: 0.8rem;">
                                <?php echo $totalItems; ?> item<?php echo $totalItems !== 1 ? 's' : ''; ?>
                            </span>
                        </div>
                    </div>
                    <a href="retrospectiva.php?sprint_id=<?php echo $sprint_id; ?>" class="btn btn-sm btn-add">
                        <i class="fas fa-comments" aria-hidden="true"></i> Ver retrospectiva
                    </a>
                </header>
                <div class="historial-categorias">
                    <?php foreach ($categoryOrder as $cat): 
                        $cat_items = array_filter($items, fn($item) => ($item['categoria'] ?? '') === $cat);
                        if (empty($cat_items)) continue;
                    ?>
                        <section class="historial-categoria" aria-labelledby="cat-<?php echo $cat; ?>-<?php echo $sprint_id; ?>">
                            <header class="historial-categoria-header <?php echo $cat; ?>">
                                <i class="fas fa-<?php echo $categoryIcons[$cat]; ?>" aria-hidden="true"></i>
                                <?php echo $categoryLabels[$cat]; ?> 
                                <span style="margin-left: 8px; font-weight: normal; color: var(--gray-500); font-size: 0.85rem;">
                                    (<?php echo count($cat_items); ?>)
                                </span>
                            </header>
                            <div class="historial-items">
                                <?php foreach ($cat_items as $item): ?>
                                    <article class="historial-item <?php echo $cat; ?> <?php echo $cat === 'accion' ? (($item['cumplida'] ?? false) ? 'cumplida' : 'no-cumplida') : ''; ?>">
                                        <p style="margin-bottom: 6px;"><?php echo htmlspecialchars($item['descripcion'] ?? ''); ?></p>
                                        <?php if ($cat === 'accion'): ?>
                                            <div class="item-fecha" style="display: flex; gap: 12px; flex-wrap: wrap;">
                                                <span style="color: <?php echo ($item['cumplida'] ?? false) ? 'var(--success)' : 'var(--danger)'; ?>">
                                                    <i class="fas fa-<?php echo ($item['cumplida'] ?? false) ? 'check-circle' : 'times-circle'; ?>" aria-hidden="true"></i>
                                                    <?php echo ($item['cumplida'] ?? false) ? 'Cumplida' : 'Pendiente'; ?>
                                                </span>
                                                <?php if (!empty($item['fecha_revision'])): ?>
                                                    <span><i class="fas fa-calendar-check" aria-hidden="true"></i> Revisar: <?php echo date('d/m/Y', strtotime($item['fecha_revision'])); ?></span>
                                                <?php endif; ?>
                                                <span><i class="fas fa-clock" aria-hidden="true"></i> <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="item-fecha">
                                                <i class="fas fa-clock" aria-hidden="true"></i> <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </article>
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