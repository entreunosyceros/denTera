<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sesion.php';
requerirLogin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || empty($data['byFecha']) || !is_array($data['byFecha'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();
    foreach ($data['byFecha'] as $fecha => $ids) {
        if (!is_string($fecha) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new RuntimeException('Fecha inválida');
        }
        if (!is_array($ids)) {
            throw new RuntimeException('Lista de citas inválida');
        }
        $orden = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $stmt = $pdo->prepare('SELECT id, fecha FROM citas WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row || $row['fecha'] !== $fecha) {
                throw new RuntimeException('La cita no coincide con el día indicado');
            }
            $pdo->prepare('UPDATE citas SET orden_agenda = ? WHERE id = ?')->execute([$orden, $id]);
            $orden += 10;
        }
    }
    $pdo->commit();
    registrarAccion($pdo, 'reordenar', 'citas', null, 'Orden manual de citas en la agenda');
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (str_contains($e->getMessage(), 'orden_agenda')) {
        http_response_code(503);
        echo json_encode([
            'ok' => false,
            'error' => 'Falta la columna orden_agenda en la base de datos. Ejecuta schema_update_orden_agenda.sql',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al guardar el orden'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
