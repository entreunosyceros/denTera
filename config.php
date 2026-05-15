<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'NOMBREBD');
define('DB_USER', 'USUARIOSQL');
define('DB_PASS', 'TUCONTRASEÑASQL');
define('DB_CHARSET', 'utf8mb4');

/**
 * Clave solo para vaciar el registro de actividad (independiente del login).
 * En entornos de prueba se muestra en pantalla; en producción sustituir por gestión segura.
 */
define('AUDITORIA_CLAVE_VACIAR', 'auditoria_prueba');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión a la base de datos. Verifique config.php y ejecute schema.sql.");
}

function getConfig($pdo, $clave, $default = '') {
    static $cache = [];
    if (!isset($cache[$clave])) {
        $stmt = $pdo->prepare("SELECT valor FROM config WHERE clave = ?");
        $stmt->execute([$clave]);
        $cache[$clave] = $stmt->fetchColumn() ?: $default;
    }
    return $cache[$clave];
}

function getAllConfig($pdo) {
    $stmt = $pdo->query("SELECT clave, valor FROM config ORDER BY clave");
    $result = [];
    while ($row = $stmt->fetch()) {
        $result[$row['clave']] = $row['valor'];
    }
    return $result;
}
