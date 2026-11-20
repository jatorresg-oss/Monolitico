<?php
require __DIR__ . '/layout/components.php';

$apiBaseUrl = '/back/usuarios_ms/public';

$titulo = "Crear Sprint";
$action = "operaciones/sprints/registrar_sprint.php";
$id = 0;
$nombre = "";
$fecha_inicio = "";
$fecha_fin = "";

if (!empty($_GET['id'])) {
    $titulo = "Modificar Sprint";
    $action = "operaciones/sprints/modificar_sprint.php";
    $id = (int)$_GET['id'];
    
    try {
        $response = file_get_contents($apiBaseUrl . "/sprints/{$id}");
        if ($response !== false) {
            $sprint = json_decode($response, true);
            if ($sprint) {
                $nombre = $sprint['nombre'] ?? '';
                $fecha_inicio = $sprint['fecha_inicio'] ?? '';
                $fecha_fin = $sprint['fecha_fin'] ?? '';
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching sprint: " . $e->getMessage());
    }
}

$pageTitle = $titulo;
$pageDescription = $id > 0 ? 'Editar sprint existente' : 'Crear un nuevo sprint para retrospectivas';
$activeTab = 'sprints';

$panelActions = [
    ['href' => 'sprints.php', 'class' => 'btn-secondary', 'icon' => 'arrow-left', 'label' => 'Volver', 'aria-label' => 'Volver a la lista de sprints']
];
if (!empty($id)) {
    array_unshift($panelActions, ['href' => "retrospectiva.php?sprint_id={$id}", 'class' => 'btn-add', 'icon' => 'comments', 'label' => 'Ver retrospectiva', 'aria-label' => "Ver retrospectiva del sprint"]);
}

ob_start();
?>
<?php echo renderPanelHeader($titulo, empty($id) ? 'plus' : 'edit', $panelActions); ?>

<form action="<?php echo $action; ?>" method="post" class="retro-form" id="sprint-form" novalidate>
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    
    <?php echo renderFormField('text', 'nombre', 'Nombre del Sprint', [
        'required' => true,
        'placeholder' => 'Ej: Sprint 1 - Autenticación de usuarios',
        'maxlength' => 255,
        'autofocus' => true
    ], $nombre, 'Nombre descriptivo para identificar el sprint'); ?>
    
    <div class="form-row">
        <?php echo renderFormField('date', 'fecha_inicio', 'Fecha Inicio', [
            'required' => true
        ], $fecha_inicio, 'Fecha de inicio del sprint'); ?>
        
        <?php echo renderFormField('date', 'fecha_fin', 'Fecha Fin', [
            'required' => true
        ], $fecha_fin, 'Fecha de finalización del sprint'); ?>
    </div>

    <div class="form-actions">
        <a href="sprints.php" class="btn btn-secondary">Cancelar</a>
        <?php if (!empty($id)): ?>
            <a href="operaciones/sprints/borrar_sprint.php?id=<?php echo (int)$id; ?>" 
               class="btn btn-danger" 
               onclick="return confirm('¿Está seguro de eliminar este sprint? Se eliminarán también todos sus items de retrospectiva.');"
               aria-label="Eliminar sprint">
                <i class="fas fa-trash" aria-hidden="true"></i> Eliminar Sprint
            </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary" id="submit-btn">
            <i class="fas fa-save" aria-hidden="true"></i> 
            <span class="btn-text"><?php echo !empty($id) ? 'Modificar Sprint' : 'Guardar Sprint'; ?></span>
        </button>
    </div>
</form>

<script>
document.getElementById('sprint-form').addEventListener('submit', function(e) {
    const inicio = document.getElementById('fecha_inicio').value;
    const fin = document.getElementById('fecha_fin').value;
    
    if (inicio && fin && inicio > fin) {
        e.preventDefault();
        showToast('La fecha de inicio no puede ser posterior a la fecha de fin', 'error');
        return false;
    }
    
    const btn = document.getElementById('submit-btn');
    btn.classList.add('loading');
    btn.disabled = true;
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout/base.php';