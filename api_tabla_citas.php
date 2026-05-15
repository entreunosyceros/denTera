<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sesion.php';
requerirLogin();

require_once __DIR__ . '/inc/citas_vista_helpers.php';
require_once __DIR__ . '/inc/citas_filtro.php';

header('Content-Type: application/json; charset=utf-8');

$get = $_GET;
[$sql, $params, $orden, $orden_dir] = citas_filtro_sql($get);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$citas = $stmt->fetchAll();

$busqueda = trim($get['busqueda'] ?? '');
$estado = $get['estado'] ?? '';
$fecha = $get['fecha'] ?? '';
$fecha_desde = $get['fecha_desde'] ?? '';
$fecha_hasta = $get['fecha_hasta'] ?? '';
$filtrosActivos = $busqueda !== '' || $estado !== '' || $fecha !== '' || $fecha_desde !== '' || $fecha_hasta !== '';

ob_start();
require __DIR__ . '/inc/tabla_citas_contenido.php';
$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'count' => count($citas),
], JSON_UNESCAPED_UNICODE);
