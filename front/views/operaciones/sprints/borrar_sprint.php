<?php
$apiBaseUrl = '/back/usuarios_ms/public';

$id = $_GET['id'] ?? '';

if (!$id) {
    header('Location: ../../sprints.php?error=1');
    exit;
}

try {
    $ch = curl_init($apiBaseUrl . "/sprints/{$id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        header('Location: ../../sprints.php?deleted=1');
    } else {
        header('Location: ../../sprints.php?error=1');
    }
} catch (Exception $e) {
    header('Location: ../../sprints.php?error=1');
}
exit;