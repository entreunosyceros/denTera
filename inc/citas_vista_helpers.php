<?php

function traduccion($e) {
    return [
        'pendiente'  => 'Pendiente',
        'confirmada' => 'Confirmada',
        'cancelada'  => 'Cancelada',
        'completada' => 'Completada',
    ][$e] ?? $e;
}

function iniciales($nombre) {
    $parts = explode(' ', trim($nombre));
    if (count($parts) >= 2) {
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }
    return strtoupper(mb_substr($nombre, 0, 2));
}

function colorAvatar($nombre) {
    $colors = ['#6366f1','#8b5cf6','#ec4899','#f43f5e','#f97316','#eab308','#22c55e','#14b8a6','#06b6d4','#3b82f6'];
    $idx = 0;
    foreach (str_split($nombre) as $c) { $idx += ord($c); }
    return $colors[$idx % count($colors)];
}

function diaSemana($fecha) {
    $dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    return $dias[date('w', strtotime($fecha))];
}

function sortUrl($col, $orden, $orden_dir, $get) {
    $params = $get;
    $params['orden'] = $col;
    if ($orden === $col) {
        $params['dir'] = ($orden_dir === 'asc') ? 'desc' : 'asc';
    } else {
        $params['dir'] = 'asc';
    }
    $arrow = '';
    if ($orden === $col) {
        $arrow = ($orden_dir === 'asc') ? ' &#x25B2;' : ' &#x25BC;';
    }
    return ['url' => '?' . http_build_query($params), 'arrow' => $arrow];
}
