<?php

/**
 * Si el texto de búsqueda es claramente una fecha concreta, devuelve Y-m-d para filtrar citas.
 */
function citas_filtro_busqueda_literal_fecha(string $busqueda): ?string {
    $b = trim($busqueda);
    if ($b === '') {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $b)) {
        $ts = strtotime($b);
        return $ts ? date('Y-m-d', $ts) : null;
    }
    if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2}|\d{4})$/', $b, $m)) {
        $d = (int) $m[1];
        $mo = (int) $m[2];
        $y = (int) $m[3];
        if ($y < 100) {
            $y += ($y >= 70 ? 1900 : 2000);
        }
        if (!checkdate($mo, $d, $y)) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }
    return null;
}

/**
 * Construye la consulta de citas con los mismos filtros que la agenda.
 *
 * @param array $get Típicamente $_GET
 * @return array{0:string,1:array,2:string,3:string} SQL, parámetros, orden, orden_dir
 */
function citas_filtro_sql(array $get): array {
    $busqueda = trim($get['busqueda'] ?? '');
    $estado = $get['estado'] ?? '';
    $fecha = $get['fecha'] ?? '';
    $fecha_desde = $get['fecha_desde'] ?? '';
    $fecha_hasta = $get['fecha_hasta'] ?? '';
    $orden = $get['orden'] ?? 'fecha_asc';
    $orden_dir = $get['dir'] ?? 'asc';

    $sql = "SELECT c.*, t.nombre as tratamiento_nombre, t.precio as tratamiento_precio,
                   d.nombre as doctor_nombre, d.especialidad as doctor_especialidad
            FROM citas c
            LEFT JOIN tratamientos t ON c.tratamiento_id = t.id
            LEFT JOIN doctores d ON c.doctor_id = d.id
            LEFT JOIN pacientes p ON c.paciente_id = p.id
            WHERE 1=1";
    $params = [];

    if ($busqueda !== '') {
        $fechaLiteral = citas_filtro_busqueda_literal_fecha($busqueda);
        $sql .= " AND (c.paciente LIKE ? OR c.telefono LIKE ? OR c.email LIKE ? OR c.dni LIKE ?
                      OR p.nombre LIKE ? OR p.telefono LIKE ? OR p.email LIKE ? OR p.dni LIKE ?";
        $kw = "%{$busqueda}%";
        for ($i = 0; $i < 8; $i++) {
            $params[] = $kw;
        }
        if ($fechaLiteral !== null) {
            $sql .= ' OR c.fecha = ?';
            $params[] = $fechaLiteral;
        }
        $sql .= ')';
    }

    if ($estado !== '') {
        $sql .= " AND c.estado = ?";
        $params[] = $estado;
    }

    if ($fecha !== '') {
        $sql .= " AND c.fecha = ?";
        $params[] = $fecha;
    }

    if ($fecha_desde !== '') {
        $sql .= " AND c.fecha >= ?";
        $params[] = $fecha_desde;
    }

    if ($fecha_hasta !== '') {
        $sql .= " AND c.fecha <= ?";
        $params[] = $fecha_hasta;
    }

    $allowed_ordenes = ['paciente', 'fecha', 'hora', 'doctor', 'motivo'];
    if (!in_array($orden, $allowed_ordenes, true)) {
        $orden = 'fecha';
    }
    if (!in_array($orden_dir, ['asc', 'desc'], true)) {
        $orden_dir = 'asc';
    }

    $sql .= " ORDER BY ";
    switch ($orden) {
        case 'paciente':
            $sql .= "c.paciente $orden_dir";
            break;
        case 'hora':
            $sql .= "c.hora $orden_dir, c.fecha $orden_dir";
            break;
        case 'doctor':
            $sql .= "d.nombre $orden_dir";
            break;
        case 'motivo':
            $sql .= "c.motivo $orden_dir";
            break;
        case 'fecha':
        default:
            $sql .= "c.fecha $orden_dir, (c.orden_agenda IS NULL) ASC, c.orden_agenda ASC, c.hora ASC";
            break;
    }

    return [$sql, $params, $orden, $orden_dir];
}
