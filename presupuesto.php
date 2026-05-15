<?php
require_once 'config.php';
require_once 'sesion.php';
requerirLogin();

$busqueda = trim($_GET['paciente'] ?? '');

$presupuesto = null;
$errores = [];

if ($busqueda !== '') {
    $stmt = $pdo->prepare("SELECT c.*, t.nombre as tratamiento_nombre, t.precio as tratamiento_precio,
                                  d.nombre as doctor_nombre
                           FROM citas c
                           LEFT JOIN tratamientos t ON c.tratamiento_id = t.id
                           LEFT JOIN doctores d ON c.doctor_id = d.id
                           WHERE c.paciente LIKE ?
                           ORDER BY c.fecha ASC, c.hora ASC");
    $stmt->execute(["%{$busqueda}%"]);
    $citas = $stmt->fetchAll();

    if (count($citas) === 0) {
        $errores[] = "No se encontraron citas para «{$busqueda}».";
    } else {
        $paciente = $citas[0]['paciente'];
        $dni = $citas[0]['dni'];
        $telefono = $citas[0]['telefono'];
        $email = $citas[0]['email'];
        $totalTratamientos = 0;
        $totalCitas = 0;
        foreach ($citas as $c) {
            if ($c['tratamiento_precio'] !== null && $c['estado'] !== 'cancelada') {
                $totalTratamientos += (float)$c['tratamiento_precio'];
            }
            if ($c['estado'] !== 'cancelada') {
                $totalCitas++;
            }
        }
        $presupuesto = [
            'paciente' => $paciente,
            'dni' => $dni,
            'telefono' => $telefono,
            'email' => $email,
            'citas' => $citas,
            'total_tratamientos' => $totalTratamientos,
            'total_citas' => $totalCitas,
            'fecha_emision' => date('d/m/Y'),
        ];
    }
}

$busquedaInput = $busqueda;

function traduccion($e) {
    return [
        'pendiente'  => 'Pendiente',
        'confirmada' => 'Confirmada',
        'cancelada'  => 'Cancelada',
        'completada' => 'Completada',
    ][$e] ?? $e;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DenTera - Presupuesto</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<?php
$header_tagline = 'Presupuestos';
$header_nav = 'page';
$header_class = 'header no-print';
require __DIR__ . '/inc/header.php';
?>

<main class="container">

    <div class="card-surface card-surface--center no-print">
        <div class="form-header">
            <div class="form-header-icon">&#x1F4B0;</div>
            <h2>Generar presupuesto</h2>
        </div>
        <form method="GET" class="card-surface-body">
            <div class="form-group">
                <label for="paciente">Buscar paciente</label>
                <input type="text" id="paciente" name="paciente" required
                       value="<?= htmlspecialchars($busquedaInput) ?>"
                       placeholder="Nombre del paciente...">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Generar presupuesto</button>
        </form>
    </div>

    <?php if (!empty($errores)): ?>
        <?php foreach ($errores as $e): ?>
            <div class="message message-error">&#x26A0; <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($presupuesto): ?>
    <div class="budget-card" id="budget-content">
        <div class="budget-header">
            <div class="budget-logo">
                <img src="img/logo.png" alt="DenTera">
            </div>
            <div class="budget-meta">
                <h2>Presupuesto</h2>
                <p>Fecha de emisión: <strong><?= $presupuesto['fecha_emision'] ?></strong></p>
            </div>
        </div>

        <div class="budget-patient">
            <h3>Datos del paciente</h3>
            <p><strong><?= htmlspecialchars($presupuesto['paciente']) ?></strong></p>
            <?php if ($presupuesto['dni']): ?>
                <p><strong>DNI:</strong> <?= htmlspecialchars($presupuesto['dni']) ?></p>
            <?php else: ?>
                <p style="color:var(--warning);font-size:0.85rem;">&#x26A0; DNI no disponible</p>
            <?php endif; ?>
            <?php if ($presupuesto['telefono']): ?>
                <p>&#x1F4DE; <?= htmlspecialchars($presupuesto['telefono']) ?></p>
            <?php endif; ?>
            <?php if ($presupuesto['email']): ?>
                <p>&#x2709; <?= htmlspecialchars($presupuesto['email']) ?></p>
            <?php endif; ?>
        </div>

        <div class="budget-summary">
            <div class="budget-summary-item">
                <span>Total de citas</span>
                <strong><?= $presupuesto['total_citas'] ?></strong>
            </div>
            <div class="budget-summary-item">
                <span>Tratamientos asignados</span>
                <strong><?= count(array_filter($presupuesto['citas'], fn($c) => $c['tratamiento_nombre'] && $c['estado'] !== 'cancelada')) ?></strong>
            </div>
        </div>

        <table class="budget-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Doctor</th>
                    <th>Tratamiento</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Precio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($presupuesto['citas'] as $c): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                    <td><?= date('H:i', strtotime($c['hora'])) ?></td>
                    <td><?= htmlspecialchars($c['doctor_nombre'] ?: '—') ?></td>
                    <td>
                        <?php if ($c['tratamiento_nombre']): ?>
                            <?= htmlspecialchars($c['tratamiento_nombre']) ?>
                        <?php else: ?>
                            <span style="color:var(--gray-400);">Sin asignar</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $c['estado'] ?>">
                            <span class="badge-dot"></span>
                            <?= traduccion($c['estado']) ?>
                        </span>
                    </td>
                    <td style="text-align:right;font-weight:600;">
                        <?php if ($c['tratamiento_precio'] !== null && $c['estado'] !== 'cancelada'): ?>
                            <?= number_format($c['tratamiento_precio'], 2) ?> €
                        <?php else: ?>
                            <span style="color:var(--gray-400);">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;font-weight:700;font-size:1.05rem;">TOTAL PRESUPUESTO</td>
                    <td style="text-align:right;font-weight:800;font-size:1.15rem;color:var(--primary);">
                        <?= number_format($presupuesto['total_tratamientos'], 2) ?> €
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="budget-notes">
            <p><strong>Notas:</strong></p>
            <ul>
                <li>Presupuesto válido durante 30 días desde la fecha de emisión.</li>
                <li>Los precios incluyen IVA.</li>
                <li>Las citas canceladas no se incluyen en el total.</li>
                <li>Este presupuesto no sustituye la factura final.</li>
            </ul>
        </div>
    </div>

    <div class="budget-actions no-print">
        <button class="btn btn-primary" onclick="window.print()">&#x1F5A8; Imprimir / Guardar PDF</button>
        <a href="index.php" class="btn btn-outline">&#x2190; Volver al listado</a>
    </div>
    <?php endif; ?>

</main>

<?php
$footer_subtitulo = 'Presupuestos';
$footer_class = 'footer no-print';
require __DIR__ . '/inc/footer.php';
?>

</body>
</html>
