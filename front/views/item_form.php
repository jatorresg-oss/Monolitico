<?php
require __DIR__ . '/layout/components.php';

$apiBaseUrl = '/back/usuarios_ms/public';

$categoria = $_GET['categoria'] ?? 'logro';
$sprint_id = isset($_GET['sprint_id']) ? (int)$_GET['sprint_id'] : 0;

$labels = [
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

$colors = [
    'logro' => 'var(--success)',
    'impedimento' => 'var(--danger)',
    'accion' => 'var(--primary)',
    'comentario' => 'var(--info)'
];

$titulo = "Agregar Item";
$action = "operaciones/retro_items/registrar_item.php";
$id = 0;
$descripcion = "";
$cumplida = false;
$fecha_revision = "";

// Mapear categoría UI a tipo API
$typeMap = [
    'logro' => 'positive',
    'impedimento' => 'negative',
    'accion' => 'action',
    'comentario' => 'positive' // default
];

if (!empty($_GET['id'])) {
    $titulo = "Modificar Item";
    $action = "operaciones/retro_items/modificar_item.php";
    $id = (int)$_GET['id'];
    
    try {
        $response = file_get_contents($apiBaseUrl . "/feedbacks/{$id}");
        if ($response !== false) {
            $item = json_decode($response, true);
            if ($item) {
                // Mapear tipo API a categoría UI
                $categoria = $item['type'] === 'positive' ? 'logro' : 
                           ($item['type'] === 'negative' ? 'impedimento' : 
                           ($item['type'] === 'action' ? 'accion' : 'comentario'));
                $sprint_id = $item['retrospective']['sprint_id'] ?? 0;
                $descripcion = $item['descripcion'] ?? '';
                $cumplida = $item['cumplida'] ?? false;
                $fecha_revision = $item['fecha_revision'] ?? '';
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching item: " . $e->getMessage());
    }
}

$sprint = null;
if ($sprint_id) {
    try {
        $response = file_get_contents($apiBaseUrl . "/sprints/{$sprint_id}");
        if ($response !== false) {
            $sprint = json_decode($response, true);
        }
    } catch (Exception $e) {
        error_log("Error fetching sprint: " . $e->getMessage());
    }
}

$pageTitle = $titulo . ' - ' . ($labels[$categoria] ?? 'Item');
$pageDescription = $sprint ? 'Sprint: ' . $sprint['nombre'] : 'Sin sprint asignado';
$activeTab = 'retrospectiva';

$panelActions = [];
if ($sprint_id) {
    $panelActions[] = ['href' => "retrospectiva.php?sprint_id={$sprint_id}", 'class' => 'btn-add', 'icon' => 'comments', 'label' => 'Ver retrospectiva', 'aria-label' => 'Ver retrospectiva del sprint'];
}
$panelActions[] = ['href' => "retrospectiva.php?sprint_id={$sprint_id}", 'class' => 'btn-secondary', 'icon' => 'arrow-left', 'label' => 'Volver', 'aria-label' => 'Volver a la retrospectiva'];

ob_start();
?>
<?php echo renderPanelHeader($titulo, empty($id) ? 'plus' : 'edit', $panelActions); ?>

<div style="max-width: 600px;">
    <form action="<?php echo $action; ?>" method="post" class="retro-form" id="item-form" novalidate>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="sprint_id" value="<?php echo $sprint_id; ?>">
        <input type="hidden" name="categoria" value="<?php echo $categoria; ?>">
        
        <!-- Category indicator -->
        <div class="category-badge <?php echo $categoria; ?>" style="margin-bottom: 20px; justify-content: center; font-size: 0.875rem;">
            <i class="fas fa-<?php echo $icons[$categoria]; ?>" aria-hidden="true"></i> 
            <?php echo $labels[$categoria]; ?>
        </div>
        
        <?php echo renderFormField('textarea', 'descripcion', 'Descripción', [
            'required' => true,
            'rows' => 5,
            'placeholder' => 'Describa el punto a tratar...',
            'autofocus' => true
        ], $descripcion, 'Escribe una descripción clara y concisa'); ?>
        
        <?php if ($categoria === 'accion'): ?>
            <?php echo renderCheckbox('cumplida', '¿Acción cumplida?', $cumplida, 'Marca si esta acción ya se completó'); ?>
            
            <?php echo renderFormField('date', 'fecha_revision', 'Fecha de revisión', [], $fecha_revision, 'Fecha opcional para revisar el cumplimiento'); ?>
        <?php endif; ?>

        <div class="form-actions">
            <a href="retrospectiva.php?sprint_id=<?php echo $sprint_id; ?>" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary" id="submit-btn">
                <i class="fas fa-save" aria-hidden="true"></i> Guardar
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('item-form').addEventListener('submit', function(e) {
    const desc = document.getElementById('descripcion').value.trim();
    if (!desc) {
        e.preventDefault();
        showToast('La descripción es obligatoria', 'error');
        document.getElementById('descripcion').focus();
        return false;
    }
    
    const btn = document.getElementById('submit-btn');
    btn.classList.add('loading');
    btn.disabled = true;
});

// Auto-resize textarea
const textarea = document.getElementById('descripcion');
textarea.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout/base.php';