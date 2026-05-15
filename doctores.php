<?php
require_once 'config.php';
require_once 'sesion.php';
requerirLogin();

$accion = $_GET['accion'] ?? '';

if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');
    $tipo = $_POST['tipo'] ?? 'doctor';
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($nombre !== '') {
        $stmt = $pdo->prepare("INSERT INTO doctores (nombre, especialidad, tipo, telefono, email, activo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $especialidad, $tipo, $telefono, $email, $activo]);
        registrarAccion($pdo, 'crear', 'doctores', null, "Nuevo profesional: $nombre");
    }
    header('Location: doctores.php');
    exit;
}

if ($accion === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');
    $tipo = $_POST['tipo'] ?? 'doctor';
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($id > 0 && $nombre !== '') {
        $stmt = $pdo->prepare("UPDATE doctores SET nombre=?, especialidad=?, tipo=?, telefono=?, email=?, activo=? WHERE id=?");
        $stmt->execute([$nombre, $especialidad, $tipo, $telefono, $email, $activo, $id]);
        registrarAccion($pdo, 'editar', 'doctores', $id, "Profesional actualizado: $nombre");
    }
    header('Location: doctores.php');
    exit;
}

if ($accion === 'eliminar') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE doctor_id = ? AND estado IN ('pendiente','confirmada')");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            header('Location: doctores.php?err=tiene_citas');
            exit;
        }
        $pdo->prepare("DELETE FROM doctores WHERE id = ?")->execute([$id]);
        registrarAccion($pdo, 'eliminar', 'doctores', $id, "Profesional eliminado ID $id");
    }
    header('Location: doctores.php');
    exit;
}

if ($accion === 'toggle') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $pdo->exec("UPDATE doctores SET activo = NOT activo WHERE id = $id");
        registrarAccion($pdo, 'editar', 'doctores', $id, "Estado toggled profesional ID $id");
    }
    header('Location: doctores.php');
    exit;
}

$stmt = $pdo->query("SELECT d.*, (SELECT COUNT(*) FROM citas c WHERE c.doctor_id = d.id AND c.estado IN ('pendiente','confirmada')) as citas_activas FROM doctores d ORDER BY d.nombre ASC");
$doctores = $stmt->fetchAll();

$editando = null;
if ($accion === 'editar' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM doctores WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editando = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DenTera - Doctores</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<?php
$header_tagline = 'Equipo clínico';
$header_nav = 'page';
require __DIR__ . '/inc/header.php';
?>

<main class="container">

    <?php if (isset($_GET['err']) && $_GET['err'] === 'tiene_citas'): ?>
        <div class="message message-error">&#x26A0; No se puede eliminar: tiene citas pendientes o confirmadas asignadas.</div>
    <?php endif; ?>

    <?php if ($editando): ?>
    <div class="form-page">
        <div class="form-card">
            <div class="form-header">
                <div class="form-header-icon">&#x270F;</div>
                <h2>Editar Profesional</h2>
            </div>
            <form method="POST" action="doctores.php?accion=editar">
                <div class="form-body">
                    <input type="hidden" name="id" value="<?= $editando['id'] ?>">
                    <div class="form-group">
                        <label for="nombre">Nombre <span class="required">*</span></label>
                        <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($editando['nombre']) ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <select id="tipo" name="tipo">
                                <option value="doctor" <?= $editando['tipo'] === 'doctor' ? 'selected' : '' ?>>Doctor/a</option>
                                <option value="higienista" <?= $editando['tipo'] === 'higienista' ? 'selected' : '' ?>>Higienista</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="especialidad">Especialidad</label>
                            <input type="text" id="especialidad" name="especialidad" value="<?= htmlspecialchars($editando['especialidad']) ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($editando['telefono']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($editando['email']) ?>">
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="activo" name="activo" value="1" <?= $editando['activo'] ? 'checked' : '' ?>>
                        <label for="activo">Activo</label>
                    </div>
                </div>
                <div class="form-footer">
                    <a href="doctores.php" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>

    <div class="toolbar toolbar--split">
        <span class="toolbar-title"><?= count($doctores) ?> profesionales registrados</span>
        <button class="btn btn-primary" onclick="document.getElementById('modal-nuevo').style.display='flex'">+ Nuevo Profesional</button>
    </div>

    <div class="table-wrapper">
        <?php if (count($doctores) === 0): ?>
            <div class="empty">
                <div class="empty-icon">&#x1F468;&#x200D;&#x2695;&#xFE0F;</div>
                <h3>No hay profesionales configurados</h3>
                <p>Añade doctores e higienistas para poder asignarlos a las citas.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th class="hide-mobile">Especialidad</th>
                        <th class="hide-mobile">Contacto</th>
                        <th>Citas activas</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctores as $d): ?>
                    <tr style="<?= !$d['activo'] ? 'opacity:0.5' : '' ?>">
                        <td>
                            <div class="patient-cell">
                                <div class="patient-avatar" style="background:<?= $d['tipo'] === 'higienista' ? '#14b8a6' : '#6366f1' ?>">
                                    <?= strtoupper(mb_substr($d['nombre'], 0, 2)) ?>
                                </div>
                                <span class="patient-name"><?= htmlspecialchars($d['nombre']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $d['tipo'] === 'higienista' ? 'badge-completada' : 'badge-pendiente' ?>">
                                <span class="badge-dot"></span>
                                <?= $d['tipo'] === 'higienista' ? 'Higienista' : 'Doctor/a' ?>
                            </span>
                        </td>
                        <td class="hide-mobile"><?= htmlspecialchars($d['especialidad'] ?: '—') ?></td>
                        <td class="hide-mobile">
                            <div class="contact-cell">
                                <?php if ($d['telefono']): ?>
                                    <span class="contact-item"><span class="contact-icon">&#x1F4DE;</span> <?= htmlspecialchars($d['telefono']) ?></span>
                                <?php endif; ?>
                                <?php if ($d['email']): ?>
                                    <span class="contact-item"><span class="contact-icon">&#x2709;</span> <?= htmlspecialchars($d['email']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><span class="time-badge"><?= (int)$d['citas_activas'] ?></span></td>
                        <td>
                            <a href="doctores.php?accion=toggle&id=<?= $d['id'] ?>" class="badge <?= $d['activo'] ? 'badge-confirmada' : 'badge-cancelada' ?>" style="text-decoration:none">
                                <span class="badge-dot"></span>
                                <?= $d['activo'] ? 'Activo' : 'Inactivo' ?>
                            </a>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="doctores.php?accion=editar&id=<?= $d['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="doctores.php?accion=eliminar&id=<?= $d['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('¿Eliminar a «<?= htmlspecialchars(addslashes($d['nombre'])) ?>»?')">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</main>

<div id="modal-nuevo" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box">
        <div class="modal-header">
            <h3>&#x1F468;&#x200D;&#x2695;&#xFE0F; Nuevo Profesional</h3>
            <button class="modal-close" onclick="this.closest('.modal-overlay').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="doctores.php?accion=crear">
            <div class="modal-body">
                <div class="form-group">
                    <label for="n-nombre">Nombre <span class="required">*</span></label>
                    <input type="text" id="n-nombre" name="nombre" required placeholder="Ej: Dra. García">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="n-tipo">Tipo</label>
                        <select id="n-tipo" name="tipo">
                            <option value="doctor">Doctor/a</option>
                            <option value="higienista">Higienista</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="n-especialidad">Especialidad</label>
                        <input type="text" id="n-especialidad" name="especialidad" placeholder="Ej: Ortodoncia">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="n-telefono">Teléfono</label>
                        <input type="tel" id="n-telefono" name="telefono" placeholder="Ej: 612345678">
                    </div>
                    <div class="form-group">
                        <label for="n-email">Email</label>
                        <input type="email" id="n-email" name="email" placeholder="Ej: doctor@clinica.com">
                    </div>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="n-activo" name="activo" value="1" checked>
                    <label for="n-activo">Activo</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal-overlay').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Profesional</button>
            </div>
        </form>
    </div>
</div>

<?php
$footer_subtitulo = 'Gestión de Profesionales';
require __DIR__ . '/inc/footer.php';
?>

</body>
</html>
