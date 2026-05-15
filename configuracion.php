<?php
require_once 'config.php';
require_once 'sesion.php';
requerirLogin();

$guardado = false;
$mensaje_auditoria = ''; // ok | error_clave

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['vaciar_auditoria'])) {
        $clave = trim($_POST['clave_auditoria'] ?? '');
        if (!hash_equals(AUDITORIA_CLAVE_VACIAR, $clave)) {
            $mensaje_auditoria = 'error_clave';
        } else {
            $pdo->exec('DELETE FROM auditoria');
            $mensaje_auditoria = 'ok';
        }
    } else {
        $stmt = $pdo->prepare("UPDATE config SET valor = ? WHERE clave = ?");
        foreach (['clinica_nombre','clinica_cif','clinica_direccion','clinica_telefono','clinica_email','clinica_web'] as $clave) {
            $valor = trim($_POST[$clave] ?? '');
            $stmt->execute([$valor, $clave]);
        }
        $guardado = true;
    }
}

$config = getAllConfig($pdo);

$stmtAudit = $pdo->query("SELECT a.*, u.nombre as usuario_nombre FROM auditoria a LEFT JOIN usuarios u ON a.usuario_id = u.id ORDER BY a.creado DESC LIMIT 50");
$auditoria = $stmtAudit->fetchAll();

$acciones_icons = [
    'crear' => '&#x2795;',
    'editar' => '&#x270F;',
    'eliminar' => '&#x274C;',
    'login' => '&#x1F511;',
    'exportar' => '&#x1F4E4;',
    'reordenar' => '&#x1F500;',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DenTera - Configuración</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<?php
$header_tagline = 'Configuración';
$header_nav = 'page';
require __DIR__ . '/inc/header.php';
?>

<main class="container">
    <div class="config-layout">
        <div class="form-page" style="margin:0;max-width:none;">
        <?php if ($guardado): ?>
            <div class="message message-success">&#x2705; Configuración guardada correctamente.</div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header">
                <div class="form-header-icon">&#x2699;</div>
                <h2>Datos de la Clínica</h2>
            </div>
            <form method="POST">
                <div class="form-body">
                    <div class="form-group">
                        <label for="clinica_nombre">Nombre de la clínica</label>
                        <input type="text" id="clinica_nombre" name="clinica_nombre"
                               value="<?= htmlspecialchars($config['clinica_nombre'] ?? '') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="clinica_cif">CIF / NIF</label>
                            <input type="text" id="clinica_cif" name="clinica_cif"
                                   value="<?= htmlspecialchars($config['clinica_cif'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="clinica_telefono">Teléfono</label>
                            <input type="tel" id="clinica_telefono" name="clinica_telefono"
                                   value="<?= htmlspecialchars($config['clinica_telefono'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="clinica_direccion">Dirección</label>
                        <input type="text" id="clinica_direccion" name="clinica_direccion"
                               value="<?= htmlspecialchars($config['clinica_direccion'] ?? '') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="clinica_email">Email</label>
                            <input type="email" id="clinica_email" name="clinica_email"
                                   value="<?= htmlspecialchars($config['clinica_email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="clinica_web">Web</label>
                            <input type="text" id="clinica_web" name="clinica_web"
                                   value="<?= htmlspecialchars($config['clinica_web'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                </div>
            </form>
        </div>
    </div>

    <div class="form-card">
        <div class="form-header">
            <div class="form-header-icon">&#x1F4CB;</div>
            <h2>Registro de Actividad</h2>
        </div>
        <div class="form-body">
            <?php if ($mensaje_auditoria === 'ok'): ?>
                <div class="message message-success">&#x2705; Registro de actividad vaciado correctamente.</div>
            <?php elseif ($mensaje_auditoria === 'error_clave'): ?>
                <div class="message message-error">&#x26A0; La clave no coincide. No se ha borrado nada.</div>
            <?php endif; ?>

            <div class="audit-demo-key" role="note">
                <strong>Entorno de prueba:</strong> para vaciar el historial usa la clave
                <code class="audit-demo-code"><?= htmlspecialchars(AUDITORIA_CLAVE_VACIAR, ENT_QUOTES, 'UTF-8') ?></code>
                (no es la misma que el inicio de sesión).
            </div>

            <form method="POST" class="audit-clear-form" onsubmit="return confirm('¿Borrar todo el registro de actividad? Esta acción no se puede deshacer.');">
                <input type="hidden" name="vaciar_auditoria" value="1">
                <div class="form-row audit-clear-row">
                    <div class="form-group audit-clear-field">
                        <label for="clave_auditoria">Clave para vaciar el registro</label>
                        <input type="text" id="clave_auditoria" name="clave_auditoria" autocomplete="off"
                               placeholder="<?= htmlspecialchars(AUDITORIA_CLAVE_VACIAR, ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group audit-clear-actions">
                        <label class="audit-clear-actions-label">&nbsp;</label>
                        <button type="submit" class="btn btn-danger">Vaciar registro</button>
                    </div>
                </div>
            </form>

            <?php if (count($auditoria) === 0): ?>
                <p style="color:var(--gray-400);text-align:center;">No hay registros de actividad.</p>
            <?php else: ?>
                <div class="audit-log">
                    <?php foreach ($auditoria as $a): ?>
                    <div class="audit-item">
                        <div class="audit-icon <?= $a['accion'] ?>">
                            <?= $acciones_icons[$a['accion']] ?? '&#x1F4CB;' ?>
                        </div>
                        <div class="audit-info">
                            <strong><?= htmlspecialchars($a['usuario_nombre'] ?: 'Desconocido') ?></strong>
                            <p><?= htmlspecialchars($a['descripcion'] ?: "{$a['accion']} en {$a['tabla']}") ?></p>
                        </div>
                        <span class="audit-time"><?= date('d/m/Y H:i', strtotime($a['creado'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    </div>
</main>

<?php
$footer_subtitulo = 'Configuración';
require __DIR__ . '/inc/footer.php';
?>

</body>
</html>
