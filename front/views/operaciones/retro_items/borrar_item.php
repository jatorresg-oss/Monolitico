<?php
$apiBaseUrl = '/back/usuarios_ms/public';

$id = $_GET['id'] ?? '';
$sprint_id = $_GET['sprint_id'] ?? '';

if (!$id) {
    header('Location: ../../retrospectiva.php?sprint_id=' . $sprint_id . '&error=1');
    exit;
}

try {
    $ch = curl_init($apiBaseUrl . "/feedbacks/{$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        header('Location: ../../retrospectiva.php?sprint_id=' . $sprint_id . '&deleted=1');
    } else {
        header('Location: ../../retrospectiva.php?sprint_id=' . $sprint_id . '&error=1');
    }
} catch (Exception $e) {
    header('Location: ../../retrospectiva.php?sprint_id=' . $sprint_id . '&error=1');
}
exit;