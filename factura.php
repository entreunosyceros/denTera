<?php
require_once 'config.php';
require_once 'sesion.php';
requerirLogin();

$id = (int)($_GET['id'] ?? 0);
$errores = [];
$factura = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT c.*, t.nombre as tratamiento_nombre, t.precio as tratamiento_precio, t.duracion as tratamiento_duracion,
                                  d.nombre as doctor_nombre, d.especialidad as doctor_especialidad
                           FROM citas c
                           LEFT JOIN tratamientos t ON c.tratamiento_id = t.id
                           LEFT JOIN doctores d ON c.doctor_id = d.id
                           WHERE c.id = ?");
    $stmt->execute([$id]);
    $cita = $stmt->fetch();

    if (!$cita) {
        $errores[] = 'Cita no encontrada.';
    } elseif ($cita['estado'] !== 'completada') {
        $errores[] = 'Solo se pueden facturar citas con estado "Completada".';
    } else {
        $config = getAllConfig($pdo);
        $iva = 0.21;
        $base = (float)($cita['tratamiento_precio'] ?? 0);
        $iva_amount = $base * $iva;
        $total = $base + $iva_amount;

        $formas_pago = [
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'transferencia' => 'Transferencia bancaria',
            'bizum' => 'Bizum',
            'seguro' => 'Seguro médico',
        ];

        $factura = [
            'numero' => sprintf('F-%s-%04d', date('Y'), $cita['id']),
            'fecha_emision' => date('d/m/Y'),
            'fecha_cita' => date('d/m/Y', strtotime($cita['fecha'])),
            'hora_cita' => date('H:i', strtotime($cita['hora'])),
            'clinica' => [
                'nombre' => $config['clinica_nombre'] ?? 'Clínica Dental DenTera',
                'cif' => $config['clinica_cif'] ?? '',
                'direccion' => $config['clinica_direccion'] ?? '',
                'telefono' => $config['clinica_telefono'] ?? '',
                'email' => $config['clinica_email'] ?? '',
                'web' => $config['clinica_web'] ?? '',
            ],
            'paciente' => [
                'nombre' => $cita['paciente'],
                'dni' => $cita['dni'] ?? '',
                'telefono' => $cita['telefono'] ?? '',
                'email' => $cita['email'] ?? '',
            ],
            'doctor' => [
                'nombre' => $cita['doctor_nombre'] ?? '',
                'especialidad' => $cita['doctor_especialidad'] ?? '',
            ],
            'tratamiento' => [
                'nombre' => $cita['tratamiento_nombre'] ?? 'Consulta general',
                'descripcion' => $cita['motivo'] ?? '',
                'duracion' => $cita['tratamiento_duracion'] ?? 0,
                'precio' => $base,
            ],
            'forma_pago' => $formas_pago[$cita['forma_pago']] ?? ($cita['forma_pago'] ?: 'No definida'),
            'base' => $base,
            'iva' => $iva_amount,
            'iva_pct' => $iva * 100,
            'total' => $total,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DenTera - Factura</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<?php
$header_tagline = 'Facturación';
$header_nav = 'page';
$header_class = 'header no-print';
require __DIR__ . '/inc/header.php';
?>

<main class="container">

    <?php if ($id === 0): ?>
    <div class="card-surface card-surface--center no-print">
        <div class="form-header">
            <div class="form-header-icon">&#x1F9FE;</div>
            <h2>Buscar factura por cita</h2>
        </div>
        <form method="GET" class="card-surface-body">
            <div class="form-group">
                <label for="id">ID de la cita</label>
                <input type="number" id="id" name="id" required min="1"
                       placeholder="Número de cita...">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Ver factura</button>
        </form>
        <p class="card-surface-hint">Solo se pueden facturar citas con estado <strong>Completada</strong>.</p>
    </div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <?php foreach ($errores as $e): ?>
            <div class="message message-error">&#x26A0; <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($factura): ?>
    <div class="invoice-card" id="invoice-content">
        <div class="invoice-header">
            <div class="invoice-clinic">
                <div class="invoice-logo">
                    <img src="img/logo.png" alt="DenTera">
                </div>
                <h2><?= htmlspecialchars($factura['clinica']['nombre']) ?></h2>
                <p><?= htmlspecialchars($factura['clinica']['direccion']) ?></p>
                <p>
                    <?php if ($factura['clinica']['telefono']): ?>
                        &#x1F4DE; <?= htmlspecialchars($factura['clinica']['telefono']) ?>
                    <?php endif; ?>
                    <?php if ($factura['clinica']['email']): ?>
                        &nbsp;|&nbsp; &#x2709; <?= htmlspecialchars($factura['clinica']['email']) ?>
                    <?php endif; ?>
                </p>
                <?php if ($factura['clinica']['cif']): ?>
                    <p><strong>CIF:</strong> <?= htmlspecialchars($factura['clinica']['cif']) ?></p>
                <?php endif; ?>
            </div>
            <div class="invoice-number">
                <h2>FACTURA</h2>
                <p class="invoice-num"><?= $factura['numero'] ?></p>
                <p>Fecha: <strong><?= $factura['fecha_emision'] ?></strong></p>
            </div>
        </div>

        <div class="invoice-parties">
            <div class="invoice-party">
                <h3>Datos del cliente</h3>
                <p><strong><?= htmlspecialchars($factura['paciente']['nombre']) ?></strong></p>
                <?php if ($factura['paciente']['dni']): ?>
                    <p><strong>DNI:</strong> <?= htmlspecialchars($factura['paciente']['dni']) ?></p>
                <?php endif; ?>
                <?php if ($factura['paciente']['telefono']): ?>
                    <p>&#x1F4DE; <?= htmlspecialchars($factura['paciente']['telefono']) ?></p>
                <?php endif; ?>
                <?php if ($factura['paciente']['email']): ?>
                    <p>&#x2709; <?= htmlspecialchars($factura['paciente']['email']) ?></p>
                <?php endif; ?>
            </div>
            <div class="invoice-party">
                <h3>Datos del tratamiento</h3>
                <p><strong><?= htmlspecialchars($factura['tratamiento']['nombre']) ?></strong></p>
                <p>Fecha cita: <?= $factura['fecha_cita'] ?> a las <?= $factura['hora_cita'] ?></p>
                <?php if ($factura['doctor']['nombre']): ?>
                    <p>Doctor: <?= htmlspecialchars($factura['doctor']['nombre']) ?></p>
                <?php endif; ?>
                <?php if ($factura['tratamiento']['duracion']): ?>
                    <p>Duración: <?= $factura['tratamiento']['duracion'] ?> minutos</p>
                <?php endif; ?>
                <p><strong>Forma de pago:</strong> <?= htmlspecialchars($factura['forma_pago']) ?></p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Descripción</th>
                    <th style="text-align:right;">Base imponible</th>
                    <th style="text-align:right;">IVA (<?= $factura['iva_pct'] ?>%)</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($factura['tratamiento']['nombre']) ?></td>
                    <td><?= htmlspecialchars($factura['tratamiento']['descripcion'] ?: '—') ?></td>
                    <td style="text-align:right;"><?= number_format($factura['base'], 2) ?> €</td>
                    <td style="text-align:right;"><?= number_format($factura['iva'], 2) ?> €</td>
                    <td style="text-align:right;font-weight:600;"><?= number_format($factura['total'], 2) ?> €</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;font-weight:700;font-size:1.05rem;">TOTAL FACTURA</td>
                    <td style="text-align:right;font-weight:800;font-size:1.2rem;color:var(--primary);">
                        <?= number_format($factura['total'], 2) ?> €
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="invoice-notes">
            <p><strong>Forma de pago:</strong> <?= htmlspecialchars($factura['forma_pago']) ?></p>
            <p>Factura generada automáticamente por DenTera. Este documento acredita el pago del tratamiento indicado.</p>
            <?php if ($factura['clinica']['cif']): ?>
                <p><?= htmlspecialchars($factura['clinica']['nombre']) ?> — CIF: <?= htmlspecialchars($factura['clinica']['cif']) ?> — <?= htmlspecialchars($factura['clinica']['direccion']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="budget-actions no-print">
        <button class="btn btn-primary" onclick="window.print()">&#x1F5A8; Imprimir / Guardar PDF</button>
        <a href="index.php" class="btn btn-outline">&#x2190; Volver al listado</a>
    </div>
    <?php endif; ?>

</main>

<?php
$footer_subtitulo = 'Facturación';
$footer_class = 'footer no-print';
require __DIR__ . '/inc/footer.php';
?>

</body>
</html>
