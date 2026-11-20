<?php
/**
 * Shared UI Components
 * Include this in views: require __DIR__ . '/layout/components.php';
 * Then use functions like: renderCard(), renderEmptyState(), etc.
 */

/**
 * Render a sprint card with stats
 */
function renderSprintCard(array $sprint, array $stats = [], bool $showActions = true): string {
    $statsHtml = '';
    if (!empty($stats)) {
        $statItems = [];
        $iconMap = [
            'logro' => 'trophy',
            'impedimento' => 'exclamation-triangle',
            'accion' => 'tasks',
            'comentario' => 'sticky-note'
        ];
        $colorMap = [
            'logro' => 'var(--success)',
            'impedimento' => 'var(--danger)',
            'accion' => 'var(--primary)',
            'comentario' => 'var(--info)'
        ];
        
        foreach ($stats as $cat => $count) {
            if ($count > 0) {
                $icon = $iconMap[$cat] ?? 'circle';
                $color = $colorMap[$cat] ?? 'var(--gray-500)';
                $statItems[] = sprintf(
                    '<span class="stat-item %s" style="--stat-color: %s"><i class="fas fa-%s" aria-hidden="true"></i> %d %s</span>',
                    $cat, $color, $icon, $count, $count === 1 ? 'item' : 'items'
                );
            }
        }
        if ($statItems) {
            $statsHtml = '<div class="sprint-card-stats">' . implode('', $statItems) . '</div>';
        }
    }
    
    $actionsHtml = '';
    if ($showActions) {
        $actionsHtml = sprintf(
            '<div class="sprint-card-actions">
                <a href="retrospectiva.php?sprint_id=%d" class="btn btn-sm btn-add" aria-label="Ver retrospectiva del sprint %s">
                    <i class="fas fa-comments" aria-hidden="true"></i> Retrospectiva
                </a>
                <a href="sprint_form.php?id=%d" class="btn btn-secondary btn-sm" aria-label="Modificar sprint %s">
                    <i class="fas fa-edit" aria-hidden="true"></i> Editar
                </a>
                <a href="operaciones/sprints/borrar_sprint.php?id=%d" class="btn btn-danger btn-sm" aria-label="Eliminar sprint %s" onclick="return confirm(\'¿Está seguro de eliminar este sprint? Se eliminarán también todos sus items de retrospectiva.\');">
                    <i class="fas fa-trash" aria-hidden="true"></i> Eliminar
                </a>
            </div>',
            $sprint['id'],
            htmlspecialchars($sprint['nombre']),
            $sprint['id'],
            htmlspecialchars($sprint['nombre']),
            $sprint['id'],
            htmlspecialchars($sprint['nombre'])
        );
    }
    
    return sprintf(
        '<article class="sprint-card" data-sprint-id="%d">
            <div class="sprint-card-header">
                <h3>%s</h3>
                %s
            </div>
            <div class="sprint-card-dates">
                <span><i class="fas fa-play" aria-hidden="true"></i> %s</span>
                <span><i class="fas fa-stop" aria-hidden="true"></i> %s</span>
            </div>
            %s
        </article>',
        $sprint['id'],
        htmlspecialchars($sprint['nombre']),
        $actionsHtml,
        date('d/m/Y', strtotime($sprint['fecha_inicio'])),
        date('d/m/Y', strtotime($sprint['fecha_fin'])),
        $statsHtml
    );
}

/**
 * Render a retro item card
 */
function renderRetroItem(array $item, int $sprintId, bool $showActions = true, array $extraClasses = []): string {
    $cat = $item['categoria'];
    $catLabels = [
        'logro' => 'Logro',
        'impedimento' => 'Impedimento',
        'accion' => 'Acción',
        'comentario' => 'Comentario'
    ];
    $icons = [
        'logro' => 'trophy',
        'impedimento' => 'exclamation-triangle',
        'accion' => 'tasks',
        'comentario' => 'sticky-note'
    ];
    
    $classes = ['retro-item', 'categoria-' . $cat];
    if ($cat === 'accion' && isset($item['cumplida']) && $item['cumplida'] !== null) {
        $classes[] = $item['cumplida'] ? 'cumplida' : 'no-cumplida';
    }
    $classes = array_merge($classes, $extraClasses);
    
    $metaParts = [];
    $metaParts[] = sprintf('<span><i class="fas fa-clock" aria-hidden="true"></i> %s</span>', 
        date('d/m/Y H:i', strtotime($item['created_at'])));
    
    if ($cat === 'accion') {
        if (isset($item['cumplida']) && $item['cumplida'] !== null) {
            $status = $item['cumplida'] ? 'Cumplida' : 'Pendiente';
            $icon = $item['cumplida'] ? 'check-circle' : 'times-circle';
            $color = $item['cumplida'] ? 'var(--success)' : 'var(--danger)';
            $metaParts[] = sprintf('<span style="color: %s"><i class="fas fa-%s" aria-hidden="true"></i> %s</span>', 
                $color, $icon, $status);
        }
        if (!empty($item['fecha_revision'])) {
            $metaParts[] = sprintf('<span><i class="fas fa-calendar-check" aria-hidden="true"></i> Revisar: %s</span>', 
                date('d/m/Y', strtotime($item['fecha_revision'])));
        }
    }
    
    $actionsHtml = '';
    if ($showActions) {
        $actionsHtml = sprintf(
            '<div class="item-actions">
                <a href="item_form.php?id=%d&categoria=%s&sprint_id=%d" class="btn btn-icon" aria-label="Editar %s" title="Editar">
                    <i class="fas fa-edit" aria-hidden="true"></i>
                </a>
                <a href="operaciones/retro_items/borrar_item.php?id=%d&sprint_id=%d" class="btn btn-icon" aria-label="Eliminar %s" title="Eliminar" onclick="return confirm(\'¿Está seguro de eliminar este item?\');">
                    <i class="fas fa-trash" aria-hidden="true"></i>
                </a>
            </div>',
            $item['id'], $cat, $sprintId, $catLabels[$cat],
            $item['id'], $sprintId, $catLabels[$cat]
        );
    }
    
    return sprintf(
        '<article class="%s" data-id="%d">
            <div class="item-icon" aria-hidden="true"><i class="fas fa-%s"></i></div>
            <div class="item-content">
                <div class="item-text">%s</div>
                <div class="item-meta">%s</div>
            </div>
            %s
        </article>',
        implode(' ', $classes),
        $item['id'],
        $icons[$cat] ?? 'circle',
        htmlspecialchars($item['descripcion']),
        implode(' ', $metaParts),
        $actionsHtml
    );
}

/**
 * Render empty state
 */
function renderEmptyState(string $icon, string $title, string $message, string $actionHtml = ''): string {
    return sprintf(
        '<div class="empty-state" role="status">
            <i class="fas fa-%s" aria-hidden="true"></i>
            <h3>%s</h3>
            <p>%s</p>
            %s
        </div>',
        $icon, htmlspecialchars($title), htmlspecialchars($message), $actionHtml
    );
}

/**
 * Render category section header
 */
function renderSectionHeader(string $icon, string $title, string $actionHtml = '', array $extraClasses = []): string {
    $classes = array_merge(['section-header'], $extraClasses);
    return sprintf(
        '<div class="%s">
            <h3><i class="fas fa-%s" aria-hidden="true"></i> %s</h3>
            %s
        </div>',
        implode(' ', $classes), $icon, htmlspecialchars($title), $actionHtml
    );
}

/**
 * Render category badge/count
 */
function renderCategoryBadge(string $category, int $count): string {
    $labels = [
        'logro' => 'Logros',
        'impedimento' => 'Impedimentos',
        'accion' => 'Acciones',
        'comentario' => 'Comentarios'
    ];
    $icons = [
        'logro' => 'trophy',
        'impedimento' => 'exclamation-triangle',
        'accion' => 'tasks',
        'comentario' => 'sticky-note'
    ];
    return sprintf(
        '<span class="category-badge %s"><i class="fas fa-%s" aria-hidden="true"></i> %s (%d)</span>',
        $category, $icons[$category] ?? 'circle', $labels[$category] ?? $category, $count
    );
}

/**
 * Render previous actions list
 */
function renderPreviousActions(array $acciones, int $currentSprintId): string {
    if (empty($acciones)) return '';
    
    $itemsHtml = '';
    foreach ($acciones as $accion) {
        $checked = $accion['cumplida'] ? 'checked' : '';
        $sprintNombre = htmlspecialchars($accion['sprint_nombre'] ?? 'Sprint anterior');
        $fecha = date('d/m/Y', strtotime($accion['created_at']));
        
        $itemsHtml .= sprintf(
            '<div class="accion-anterior-item">
                <form action="operaciones/retro_items/actualizar_accion_cumplida.php" method="post" class="accion-anterior-form">
                    <input type="hidden" name="id" value="%d">
                    <input type="hidden" name="sprint_id" value="%d">
                    <label class="checkbox-label">
                        <input type="checkbox" name="cumplida" value="1" %s onchange="this.form.submit()" aria-label="Marcar acción como cumplida">
                        <span class="checkmark" aria-hidden="true"></span>
                    </label>
                    <div class="accion-anterior-texto">%s</div>
                    <div class="accion-anterior-fecha">%s - %s</div>
                </form>
            </div>',
            $accion['id'], $currentSprintId, $checked,
            htmlspecialchars($accion['descripcion']),
            $sprintNombre, $fecha
        );
    }
    
    return sprintf(
        '<section class="acciones-anteriores" aria-labelledby="prev-actions-title">
            <h3 id="prev-actions-title"><i class="fas fa-history" aria-hidden="true"></i> Acciones de Sprints Anteriores</h3>
            <p class="help-text">Revise el cumplimiento de las acciones acordadas:</p>
            <div class="acciones-list">%s</div>
        </section>',
        $itemsHtml
    );
}

/**
 * Render page header with actions
 */
function renderPanelHeader(string $title, string $icon, array $actions = []): string {
    $actionsHtml = '';
    if (!empty($actions)) {
        $actionsHtml = '<div class="panel-actions">';
        foreach ($actions as $action) {
            $attrs = '';
            if (!empty($action['href'])) $attrs .= ' href="' . $action['href'] . '"';
            if (!empty($action['onclick'])) $attrs .= ' onclick="' . $action['onclick'] . '"';
            if (!empty($action['aria-label'])) $attrs .= ' aria-label="' . $action['aria-label'] . '"';
            $actionsHtml .= sprintf('<a class="btn %s"%s><i class="fas fa-%s" aria-hidden="true"></i> %s</a>',
                $action['class'] ?? 'btn-secondary', $attrs, $action['icon'] ?? 'circle', $action['label']);
        }
        $actionsHtml .= '</div>';
    }
    
    return sprintf(
        '<div class="panel-header">
            <h2><i class="fas fa-%s" aria-hidden="true"></i> %s</h2>
            %s
        </div>',
        $icon, htmlspecialchars($title), $actionsHtml
    );
}

/**
 * Render sprint selector dropdown
 */
function renderSprintSelector(array $sprints, ?int $currentId = null, string $label = 'Seleccionar Sprint:'): string {
    $options = '<option value="">-- ' . htmlspecialchars($label) . ' --</option>';
    foreach ($sprints as $sprint) {
        $selected = $currentId == $sprint['id'] ? 'selected' : '';
        $options .= sprintf(
            '<option value="%d" %s>%s (%s - %s)</option>',
            $sprint['id'], $selected,
            htmlspecialchars($sprint['nombre']),
            date('d/m/Y', strtotime($sprint['fecha_inicio'])),
            date('d/m/Y', strtotime($sprint['fecha_fin']))
        );
    }
    
    return sprintf(
        '<div class="sprint-selector">
            <label for="select-sprint">%s</label>
            <select id="select-sprint" class="form-control" onchange="window.location.href=\'retrospectiva.php?sprint_id=\' + this.value" aria-label="%s">
                %s
            </select>
        </div>',
        htmlspecialchars($label), htmlspecialchars($label), $options
    );
}

/**
 * Render form field
 */
function renderFormField(string $type, string $name, string $label, array $attrs = [], ?string $value = null, ?string $help = null): string {
    $attrStr = '';
    foreach ($attrs as $k => $v) {
        if ($k === 'required' && $v === true) $attrStr .= ' required';
        elseif ($v !== false && $v !== null) $attrStr .= sprintf(' %s="%s"', $k, htmlspecialchars((string)$v));
    }
    
    $required = isset($attrs['required']) && $attrs['required'] ? '<span class="required" aria-hidden="true">*</span>' : '';
    
    $input = '';
    if ($type === 'textarea') {
        $input = sprintf('<textarea name="%s" id="%s"%s>%s</textarea>', $name, $name, $attrStr, htmlspecialchars($value ?? ''));
    } elseif ($type === 'select') {
        $options = $attrs['options'] ?? [];
        unset($attrs['options']);
        $attrStr = '';
        foreach ($attrs as $k => $v) $attrStr .= sprintf(' %s="%s"', $k, htmlspecialchars((string)$v));
        $optsHtml = '';
        foreach ($options as $optVal => $optLabel) {
            $sel = $value == $optVal ? 'selected' : '';
            $optsHtml .= sprintf('<option value="%s" %s>%s</option>', htmlspecialchars((string)$optVal), $sel, htmlspecialchars((string)$optLabel));
        }
        $input = sprintf('<select name="%s" id="%s"%s>%s</select>', $name, $name, $attrStr, $optsHtml);
    } else {
        $input = sprintf('<input type="%s" name="%s" id="%s" value="%s"%s>', 
            $type, $name, $name, htmlspecialchars($value ?? ''), $attrStr);
    }
    
    $helpHtml = $help ? sprintf('<p class="help-text">%s</p>', htmlspecialchars($help)) : '';
    
    return sprintf(
        '<div class="form-group">
            <label for="%s">%s %s</label>
            %s
            %s
        </div>',
        $name, htmlspecialchars($label), $required, $input, $helpHtml
    );
}

/**
 * Render checkbox field
 */
function renderCheckbox(string $name, string $label, bool $checked = false, string $help = ''): string {
    $helpHtml = $help ? sprintf('<p class="help-text">%s</p>', htmlspecialchars($help)) : '';
    return sprintf(
        '<div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="%s" value="1" %s aria-label="%s">
                <span class="checkmark" aria-hidden="true"></span>
                <span>%s</span>
            </label>
            %s
        </div>',
        $name, $checked ? 'checked' : '', htmlspecialchars($label), htmlspecialchars($label), $helpHtml
    );
}