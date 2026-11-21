<?php

/**
 * Inicializa la base de datos del proyecto usando back/registro_retro_db.sql.
 *
 * Uso:  php back/setup_database.php
 *
 * Es seguro ejecutarlo varias veces: el SQL usa CREATE TABLE IF NOT EXISTS e
 * INSERT IGNORE, por lo que no borra ni duplica los datos existentes.
 */

require __DIR__ . '/sprint-service/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$config = [
    'driver'    => 'mysql',
    'host'      => getenv('DB_HOST') ?: '127.0.0.1',
    'port'      => getenv('DB_PORT') ?: 3306,
    'database'  => 'mysql', // base existente; el SQL crea y selecciona registro_retro_db
    'username'  => getenv('DB_USER') ?: 'root',
    'password'  => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
];

$capsule = new Capsule;
$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$pdo = $capsule->getConnection()->getPdo();
$sqlFile = __DIR__ . '/registro_retro_db.sql';

if (!is_file($sqlFile)) {
    fwrite(STDERR, "No se encontró {$sqlFile}\n");
    exit(1);
}

try {
    $pdo->exec(file_get_contents($sqlFile));

    $sprints = $pdo->query('SELECT COUNT(*) FROM sprints')->fetchColumn();
    $items = $pdo->query('SELECT COUNT(*) FROM retro_items')->fetchColumn();

    echo "Base registro_retro_db lista.\n";
    echo "Sprints: {$sprints}\n";
    echo "Items de retrospectiva: {$items}\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Error al inicializar la base: ' . $e->getMessage() . "\n");
    exit(1);
}
