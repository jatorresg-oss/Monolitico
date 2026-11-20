<?php
$apiBaseUrl = '/back/usuarios_ms/public';

$id = $_POST['id'] ?? '';
$sprint_id = $_POST['sprint_id'] ?? '';
$cumplida = isset($_POST['cumplida']) ? true : false;

if (!$id) {
    header('Location: ../../retrospectiva.php?sprint_id=' . $sprint_id . '&error=1');
    exit;
}

// Actualizar via action-tracking endpoint
$data = [
    'original_feedback_id' => $id,
    'review_sprint_id' => $sprint_id,
    'status' => $cumplida ? 'completed' : 'not_completed',
    'completion_percentage' => $cumplida ? 100 : 0,
    'comments' => '',
    'reviewed_by' => 'Usuario'
];

try {
    $ch = curl_init($apiBaseUrl . '/action-tracking');
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
        header('Location: ../../retrospectiva.php?sprint_id=' . $sprint_id . '&error=1');
    }
} catch (Exception $e) {
    header('Location: ../../retrospectiva.php?sprint_id=' . $sprint_id . '&error=1');
}
exit;