<?php
$apiBaseUrl = '/back/usuarios_ms/public';

$sprint_id = $_POST['sprint_id'] ?? '';
$categoria = $_POST['categoria'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$cumplida = isset($_POST['cumplida']) ? true : false;
$fecha_revision = $_POST['fecha_revision'] ?? '';

if (!$sprint_id || !$categoria || !$descripcion) {
    header('Location: ../../item_form.php?sprint_id=' . $sprint_id . '&categoria=' . $categoria . '&error=1');
    exit;
}

// Mapear categoría UI a tipo API
$typeMap = [
    'logro' => 'positive',
    'impedimento' => 'negative',
    'accion' => 'action',
    'comentario' => 'positive'
];

// Primero obtener o crear retrospectiva para el sprint
try {
    $ch = curl_init($apiBaseUrl . "/retrospectives/sprint/{$sprint_id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $retro = null;
    if ($httpCode === 200) {
        $retro = json_decode($response, true);
    } else {
        // Crear retrospectiva
        $ch = curl_init($apiBaseUrl . '/retrospectives');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'sprint_id' => $sprint_id,
            'facilitator' => '',
            'date' => date('Y-m-d'),
            'duration_minutes' => 60,
            'status' => 'in_progress',
            'notes' => ''
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $retro = json_decode($response, true);
        }
    }
    
    if (!$retro || !isset($retro['id'])) {
        header('Location: ../../retrospectiva.php?sprint_id=' . $sprint_id . '&error=1');
        exit;
    }
    
    // Crear feedback item
    $data = [
        'retrospective_id' => $retro['id'],
        'type' => $typeMap[$categoria] ?? 'positive',
        'title' => $descripcion,
        'description' => $descripcion,
        'priority' => 'medium',
        'assignee' => null,
        'due_date' => $fecha_revision ?: null,
        'order_index' => 0
    ];
    
    $ch = curl_init($apiBaseUrl . '/feedbacks');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        header('Location: ../../retrospectiva.php?sprint_id=' . $sprint_id . '&success=1');
    } else {
        header('Location: ../../item_form.php?sprint_id=' . $sprint_id . '&categoria=' . $categoria . '&error=1');
    }
} catch (Exception $e) {
    header('Location: ../../item_form.php?sprint_id=' . $sprint_id . '&categoria=' . $categoria . '&error=1');
}
exit;