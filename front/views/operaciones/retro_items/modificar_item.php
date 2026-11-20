<?php
$apiBaseUrl = '/back/usuarios_ms/public';

$id = $_POST['id'] ?? '';
$sprint_id = $_POST['sprint_id'] ?? '';
$categoria = $_POST['categoria'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$cumplida = isset($_POST['cumplida']) ? true : false;
$fecha_revision = $_POST['fecha_revision'] ?? '';

if (!$id || !$descripcion) {
    header('Location: ../../item_form.php?id=' . $id . '&sprint_id=' . $sprint_id . '&categoria=' . $categoria . '&error=1');
    exit;
}

$typeMap = [
    'logro' => 'positive',
    'impedimento' => 'negative',
    'accion' => 'action',
    'comentario' => 'positive'
];

$data = [
    'title' => $descripcion,
    'description' => $descripcion,
    'priority' => 'medium'
];

if ($categoria === 'accion') {
    $data['cumplida'] = $cumplida;
    $data['fecha_revision'] = $fecha_revision ?: null;
}

try {
    $ch = curl_init($apiBaseUrl . "/feedbacks/{$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        header('Location: ../../retrospectiva.php?sprint_id=' . $sprint_id . '&success=1');
    } else {
        header('Location: ../../item_form.php?id=' . $id . '&sprint_id=' . $sprint_id . '&categoria=' . $categoria . '&error=1');
    }
} catch (Exception $e) {
    header('Location: ../../item_form.php?id=' . $id . '&sprint_id=' . $sprint_id . '&categoria=' . $categoria . '&error=1');
}
exit;