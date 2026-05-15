<?php
require_once 'config.php';
require_once 'sesion.php';
requerirLogin();

$accion = $_GET['accion'] ?? '';

if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $duracion = (int)($_POST['duracion'] ?? 30);
    $precio = (float)($_POST['precio'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($nombre !== '') {
        $stmt = $pdo->prepare("INSERT INTO tratamientos (nombre, descripcion, duracion, precio, activo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $descripcion, $duracion, $precio, $activo]);
        registrarAccion($pdo, 'crear', 'tratamientos', null, "Nuevo tratamiento: $nombre");
    }
    header('Location: tratamientos.php');
    exit;
}

if ($accion === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $duracion = (int)($_POST['duracion'] ?? 30);
    $precio = (float)($_POST['precio'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($id > 0 && $nombre !== '') {
        $stmt = $pdo->prepare("UPDATE tratamientos SET nombre=?, descripcion=?, duracion=?, precio=?, activo=? WHERE id=?");
        $stmt->execute([$nombre, $descripcion, $duracion, $precio, $activo, $id]);
        registrarAccion($pdo, 'editar', 'tratamientos', $id, "Tratamiento actualizado: $nombre");
    }
    header('Location: tratamientos.php');
    exit;
}

if ($accion === 'eliminar') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM tratamientos WHERE id = ?");
        $stmt->execute([$id]);
        registrarAccion($pdo, 'eliminar', 'tratamientos', $id, "Tratamiento eliminado ID $id");
    }
    header('Location: tratamientos.php');
    exit;
}

if ($accion === 'toggle') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $pdo->exec("UPDATE tratamientos SET activo = NOT activo WHERE id = $id");
        registrarAccion($pdo, 'editar', 'tratamientos', $id, "Estado toggled tratamiento ID $id");
    }
    header('Location: tratamientos.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM tratamientos ORDER BY nombre ASC");
$tratamientos = $stmt->fetchAll();

$editando = null;
if ($accion === 'editar' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM tratamientos WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editando = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DenTera - Tratamientos</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<?php
$header_tagline = 'Tratamientos';
$header_nav = 'page';
require __DIR__ . '/inc/header.php';
?>

<main class="container">

    <?php if ($editando): ?>
    <div class="form-page">
        <div class="form-card">
            <div class="form-header">
                <div class="form-header-icon">&#x270F;</div>
                <h2>Editar Tratamiento</h2>
            </div>
            <form method="POST" action="tratamientos.php?accion=editar">
                <div class="form-body">
                    <input type="hidden" name="id" value="<?= $editando['id'] ?>">
                    <div class="form-group">
                        <label for="nombre">Nombre <span class="required">*</span></label>
                        <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($editando['nombre']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="2"><?= htmlspecialchars($editando['descripcion']) ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="duracion">Duración (minutos)</label>
                            <input type="number" id="duracion" name="duracion" min="5" max="480" step="5" value="<?= (int)$editando['duracion'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="precio">Precio (€)</label>
                            <input type="number" id="precio" name="precio" min="0" step="0.01" value="<?= number_format($editando['precio'], 2, '.', '') ?>">
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="activo" name="activo" value="1" <?= $editando['activo'] ? 'checked' : '' ?>>
                        <label for="activo">Activo</label>
                    </div>
                </div>
                <div class="form-footer">
                    <a href="tratamientos.php" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>

    <div class="toolbar toolbar--split">
        <span class="toolbar-title"><?= count($tratamientos) ?> tratamientos configurados</span>
        <button class="btn btn-primary" onclick="document.getElementById('modal-nuevo').style.display='flex'">+ Nuevo Tratamiento</button>
    </div>

    <div class="table-wrapper">
        <?php if (count($tratamientos) === 0): ?>
            <div class="empty">
                <div class="empty-icon">&#x1F48A;</div>
                <h3>No hay tratamientos configurados</h3>
                <p>Añade el primer tratamiento para poder asignarlo a las citas.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th class="hide-mobile">Descripción</th>
                        <th>Duración</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tratamientos as $t): ?>
                    <tr style="<?= !$t['activo'] ? 'opacity:0.5' : '' ?>">
                        <td><strong><?= htmlspecialchars($t['nombre']) ?></strong></td>
                        <td class="hide-mobile"><?= htmlspecialchars(mb_strlen($t['descripcion']) > 60 ? mb_substr($t['descripcion'], 0, 60) . '...' : $t['descripcion']) ?></td>
                        <td><span class="time-badge">&#x23F1; <?= $t['duracion'] ?> min</span></td>
                        <td><strong><?= number_format($t['precio'], 2) ?> €</strong></td>
                        <td>
                            <a href="tratamientos.php?accion=toggle&id=<?= $t['id'] ?>" class="badge <?= $t['activo'] ? 'badge-confirmada' : 'badge-cancelada' ?>" style="text-decoration:none">
                                <span class="badge-dot"></span>
                                <?= $t['activo'] ? 'Activo' : 'Inactivo' ?>
                            </a>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="tratamientos.php?accion=editar&id=<?= $t['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="tratamientos.php?accion=eliminar&id=<?= $t['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('¿Eliminar el tratamiento «<?= htmlspecialchars(addslashes($t['nombre'])) ?>»?')">Eliminar</a>
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

<!-- Modal nuevo tratamiento -->
<div id="modal-nuevo" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box">
        <div class="modal-header">
            <h3>&#x1F48A; Nuevo Tratamiento</h3>
            <button class="modal-close" onclick="this.closest('.modal-overlay').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="tratamientos.php?accion=crear">
            <div class="modal-body">
                <div class="form-group">
                    <label for="n-nombre">Nombre <span class="required">*</span></label>
                    <input type="text" id="n-nombre" name="nombre" required placeholder="Ej: Blanqueamiento">
                </div>
                <div class="form-group">
                    <label for="n-descripcion">Descripción</label>
                    <textarea id="n-descripcion" name="descripcion" rows="2" placeholder="Breve descripción del tratamiento..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="n-duracion">Duración (min)</label>
                        <input type="number" id="n-duracion" name="duracion" min="5" max="480" step="5" value="30">
                    </div>
                    <div class="form-group">
                        <label for="n-precio">Precio (€)</label>
                        <input type="number" id="n-precio" name="precio" min="0" step="0.01" value="0.00">
                    </div>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="n-activo" name="activo" value="1" checked>
                    <label for="n-activo">Activo</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="this.closest('.modal-overlay').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-primary">Crear Tratamiento</button>
            </div>
        </form>
    </div>
</div>

<?php
$footer_subtitulo = 'Gestión de Tratamientos';
require __DIR__ . '/inc/footer.php';
?>

</body>
</html>
