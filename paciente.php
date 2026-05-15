<?php
require_once 'config.php';
require_once 'sesion.php';
requerirLogin();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$msgNotasOk = isset($_GET['ok']) && $_GET['ok'] === 'notas';
$errorNotas = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_notas_clinicas'])) {
    $txt = trim($_POST['notas_clinicas'] ?? '');
    try {
        $pdo->prepare('UPDATE pacientes SET notas_clinicas = ? WHERE id = ?')->execute([$txt, $id]);
        registrarAccion($pdo, 'editar', 'pacientes', $id, 'Notas clínicas actualizadas');
        header('Location: paciente.php?id=' . $id . '&ok=notas');
        exit;
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'notas_clinicas') || str_contains($e->getMessage(), 'Unknown column')) {
            $errorNotas = 'Falta la columna notas_clinicas: ejecuta el bloque de actualización al final de schema.sql (o importa de nuevo ese archivo sobre la misma base).';
        } else {
            $errorNotas = 'No se pudieron guardar las notas.';
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM pacientes WHERE id = ?');
$stmt->execute([$id]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: index.php');
    exit;
}

$stmtCitas = $pdo->prepare("SELECT c.*, t.nombre as tratamiento_nombre, t.precio as tratamiento_precio,
                                   d.nombre as doctor_nombre, d.especialidad as doctor_especialidad
                            FROM citas c
                            LEFT JOIN tratamientos t ON c.tratamiento_id = t.id
                            LEFT JOIN doctores d ON c.doctor_id = d.id
                            WHERE c.paciente_id = ?
                            ORDER BY c.fecha ASC, c.hora ASC");
$stmtCitas->execute([$id]);
$citas = $stmtCitas->fetchAll();

$totalGastado = 0;
$totalCitas = count($citas);
$citasCompletadas = 0;
foreach ($citas as $c) {
    if ($c['tratamiento_precio'] !== null && $c['estado'] !== 'cancelada') {
        $totalGastado += (float) $c['tratamiento_precio'];
    }
    if ($c['estado'] === 'completada') {
        $citasCompletadas++;
    }
}

function traduccion($e) {
    return [
        'pendiente'  => 'Pendiente',
        'confirmada' => 'Confirmada',
        'cancelada'  => 'Cancelada',
        'completada' => 'Completada',
    ][$e] ?? $e;
}

function diaSemana($fecha) {
    $dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    return $dias[date('w', strtotime($fecha))];
}

$formas_pago = [
    'efectivo' => 'Efectivo',
    'tarjeta' => 'Tarjeta',
    'transferencia' => 'Transferencia',
    'bizum' => 'Bizum',
    'seguro' => 'Seguro médico',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DenTera - Historial de <?= htmlspecialchars($paciente['nombre']) ?></title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<?php
$header_tagline = 'Historial de paciente';
$header_nav = 'page';
$header_class = 'header no-print';
require __DIR__ . '/inc/header.php';
?>

<main class="container">

    <div class="patient-history-header">
        <div class="patient-history-main">
            <div class="patient-avatar-lg" style="background:<?= colorAvatar($paciente['nombre']) ?>">
                <?= iniciales($paciente['nombre']) ?>
            </div>
            <div class="patient-info-lg">
                <h2><?= htmlspecialchars($paciente['nombre']) ?></h2>
                <div class="patient-meta">
                    <?php if ($paciente['dni']): ?>
                        <span>&#x1F4C3; <?= htmlspecialchars($paciente['dni']) ?></span>
                    <?php endif; ?>
                    <?php if ($paciente['telefono']): ?>
                        <span>&#x1F4DE; <?= htmlspecialchars($paciente['telefono']) ?></span>
                    <?php endif; ?>
                    <?php if ($paciente['email']): ?>
                        <span>&#x2709; <?= htmlspecialchars($paciente['email']) ?></span>
                    <?php endif; ?>
                    <span>&#x1F4C5; Cliente desde <?= date('d/m/Y', strtotime($paciente['creado'])) ?></span>
                </div>
            </div>
        </div>
        <div class="patient-history-actions no-print">
            <a href="crear.php?desde_paciente=<?= $id ?>" class="btn btn-primary">+ Nueva cita</a>
        </div>
    </div>

    <?php if ($msgNotasOk): ?>
        <div class="message message-success">&#x2705; Notas clínicas guardadas.</div>
    <?php endif; ?>
    <?php if (!empty($errorNotas)): ?>
        <div class="message message-error">&#x26A0; <?= htmlspecialchars($errorNotas) ?></div>
    <?php endif; ?>

    <div class="form-card notas-clinicas-card no-print">
        <div class="form-header">
            <div class="form-header-icon">&#x1F4CB;</div>
            <h2>Notas clínicas</h2>
        </div>
        <form method="post" class="form-body">
            <p class="notas-clinicas-hint">Alergias, medicación, antecedentes u observaciones permanentes del paciente (no sustituyen al historial clínico completo).</p>
            <div class="form-group full">
                <label for="notas_clinicas">Observaciones</label>
                <textarea id="notas_clinicas" name="notas_clinicas" rows="5" class="notas-clinicas-textarea"
                          placeholder="Ej: Alergia penicilina, diabético, implante pieza 14…"><?= htmlspecialchars($paciente['notas_clinicas'] ?? '') ?></textarea>
            </div>
            <input type="hidden" name="guardar_notas_clinicas" value="1">
            <div class="form-footer" style="border-top:none;padding-top:0;">
                <button type="submit" class="btn btn-primary">Guardar notas</button>
            </div>
        </form>
    </div>

    <div class="stats">
        <div class="stat-card blue">
            <div class="stat-icon">&#x1F4CB;</div>
            <div class="stat-info">
                <div class="stat-num"><?= $totalCitas ?></div>
                <div class="stat-label">Total de citas</div>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">&#x2705;</div>
            <div class="stat-info">
                <div class="stat-num"><?= $citasCompletadas ?></div>
                <div class="stat-label">Completadas</div>
            </div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon">&#x1F4B0;</div>
            <div class="stat-info">
                <div class="stat-num"><?= number_format($totalGastado, 2) ?> €</div>
                <div class="stat-label">Total gastado</div>
            </div>
        </div>
        <div class="stat-card cyan">
            <div class="stat-icon">&#x1F4C5;</div>
            <div class="stat-info">
                <div class="stat-num"><?= count(array_filter($citas, fn($c) => strtotime($c['fecha']) >= strtotime(date('Y-m-d')) && $c['estado'] !== 'cancelada')) ?></div>
                <div class="stat-label">Citas futuras</div>
            </div>
        </div>
    </div>

    <div class="table-wrapper">
        <?php if (count($citas) === 0): ?>
            <div class="empty">
                <div class="empty-icon">&#x1F4CB;</div>
                <h3>Sin citas registradas</h3>
                <p>Este paciente no tiene citas en el sistema.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Dentista</th>
                        <th>Tratamiento</th>
                        <th>Estado</th>
                        <th class="hide-mobile">Forma de pago</th>
                        <th class="hide-mobile">Precio</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $num = 0; foreach ($citas as $c): $num++; ?>
                    <tr>
                        <td><strong><?= $num ?></strong></td>
                        <td>
                            <div class="date-cell">
                                <span class="date-main"><?= date('d/m/Y', strtotime($c['fecha'])) ?></span>
                                <span class="date-day"><?= diaSemana($c['fecha']) ?></span>
                            </div>
                        </td>
                        <td><span class="time-badge"><?= date('H:i', strtotime($c['hora'])) ?></span></td>
                        <td><?= htmlspecialchars($c['doctor_nombre'] ?: '—') ?></td>
                        <td>
                            <?php if ($c['tratamiento_nombre']): ?>
                                <span class="treatment-badge"><?= htmlspecialchars($c['tratamiento_nombre']) ?></span>
                            <?php else: ?>
                                <span style="color:var(--gray-400);font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $c['estado'] ?>">
                                <span class="badge-dot"></span>
                                <?= traduccion($c['estado']) ?>
                            </span>
                        </td>
                        <td class="hide-mobile">
                            <?php if ($c['forma_pago']): ?>
                                <?= htmlspecialchars($formas_pago[$c['forma_pago']] ?? $c['forma_pago']) ?>
                            <?php else: ?>
                                <span style="color:var(--gray-400);">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="hide-mobile" style="font-weight:600;">
                            <?php if ($c['tratamiento_precio'] !== null && $c['estado'] !== 'cancelada'): ?>
                                <?= number_format($c['tratamiento_precio'], 2) ?> €
                            <?php else: ?>
                                <span style="color:var(--gray-400);">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="editar.php?id=<?= $c['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="duplicar_cita.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm" title="Nueva cita con los mismos datos">Duplicar</a>
                                <?php if ($c['estado'] === 'completada'): ?>
                                    <a href="factura.php?id=<?= $c['id'] ?>" class="btn btn-success btn-sm" title="Ver factura">&#x1F9FE;</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php
$footer_subtitulo = 'Historial de Paciente';
$footer_class = 'footer no-print';
require __DIR__ . '/inc/footer.php';
?>

</body>
</html>

<?php
function colorAvatar($nombre) {
    $colors = ['#6366f1','#8b5cf6','#ec4899','#f43f5e','#f97316','#eab308','#22c55e','#14b8a6','#06b6d4','#3b82f6'];
    $idx = 0;
    foreach (str_split($nombre) as $c) { $idx += ord($c); }
    return $colors[$idx % count($colors)];
}

function iniciales($nombre) {
    $parts = explode(' ', trim($nombre));
    if (count($parts) >= 2) {
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }
    return strtoupper(mb_substr($nombre, 0, 2));
}
?>
