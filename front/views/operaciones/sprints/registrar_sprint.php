<?php
$apiBaseUrl = '/back/usuarios_ms/public';

$nombre = $_POST['nombre'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';

if (!$nombre || !$fecha_inicio || !$fecha_fin) {
    header('Location: ../../sprint_form.php?error=1');
    exit;
}

$data = [
    'team_id' => 1, // Default team for now
    'name' => $nombre,
    'number' => 1, // Will be auto-calculated ideally
    'goal' => '',
    'start_date' => $fecha_inicio,
    'end_date' => $fecha_fin,
    'status' => 'planned'
];

try {
    $ch = curl_init($apiBaseUrl . '/sprints');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        header('Location: ../../sprints.php?success=1');
    } else {
        header('Location: ../../sprint_form.php?error=1');
    }
} catch (Exception $e) {
    header('Location: ../../sprint_form.php?error=1');
}
exit;