<?php
$apiBaseUrl = '/back/usuarios_ms/public';

$id = $_POST['id'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';

if (!$id || !$nombre || !$fecha_inicio || !$fecha_fin) {
    header('Location: ../../sprint_form.php?id=' . $id . '&error=1');
    exit;
}

$data = [
    'name' => $nombre,
    'start_date' => $fecha_inicio,
    'end_date' => $fecha_fin
];

try {
    $ch = curl_init($apiBaseUrl . "/sprints/{$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        header('Location: ../../sprints.php?success=1');
    } else {
        header('Location: ../../sprint_form.php?id=' . $id . '&error=1');
    }
} catch (Exception $e) {
    header('Location: ../../sprint_form.php?id=' . $id . '&error=1');
}
exit;