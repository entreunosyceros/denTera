<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sesion.php';
requerirLogin();

require_once __DIR__ . '/inc/citas_filtro.php';

$get = $_GET;
[$sql, $params] = citas_filtro_sql($get);
$sql .= ' LIMIT 10000';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fn = 'citas_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fn . '"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

$headers = [
    'ID', 'Fecha', 'Hora', 'Paciente', 'DNI', 'Teléfono', 'Email',
    'Doctor', 'Tratamiento', 'Estado', 'Motivo', 'Forma de pago', 'Notas',
];
fputcsv($out, $headers, ';');

foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'],
        $r['fecha'],
        $r['hora'],
        $r['paciente'],
        $r['dni'],
        $r['telefono'],
        $r['email'],
        $r['doctor_nombre'] ?? '',
        $r['tratamiento_nombre'] ?? '',
        $r['estado'],
        $r['motivo'],
        $r['forma_pago'],
        $r['notas'] ?? '',
    ], ';');
}

fclose($out);
registrarAccion($pdo, 'exportar', 'citas', null, 'Exportación CSV de citas (' . count($rows) . ' filas)');
exit;
