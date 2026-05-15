<?php
require_once 'config.php';
require_once 'sesion.php';
requerirLogin();

$stmtTrat = $pdo->query("SELECT * FROM tratamientos WHERE activo = 1 ORDER BY nombre ASC");
$tratamientos = $stmtTrat->fetchAll();

$stmtDoc = $pdo->query("SELECT * FROM doctores WHERE activo = 1 ORDER BY nombre ASC");
$doctores = $stmtDoc->fetchAll();

$errores = [];
$datos = [
    'paciente'       => '',
    'dni'            => '',
    'telefono'       => '',
    'email'          => '',
    'fecha'          => date('Y-m-d'),
    'hora'           => '10:00',
    'doctor_id'      => '',
    'tratamiento_id' => '',
    'motivo'         => '',
    'estado'         => 'pendiente',
    'forma_pago'     => '',
    'notas'          => '',
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $desdeCita = (int) ($_GET['desde_cita'] ?? 0);
    $desdePaciente = (int) ($_GET['desde_paciente'] ?? 0);

    if ($desdeCita > 0) {
        $stmtOrig = $pdo->prepare('SELECT * FROM citas WHERE id = ?');
        $stmtOrig->execute([$desdeCita]);
        $orig = $stmtOrig->fetch();
        if ($orig) {
            $datos['paciente'] = $orig['paciente'];
            $datos['dni'] = $orig['dni'] ?? '';
            $datos['telefono'] = $orig['telefono'] ?? '';
            $datos['email'] = $orig['email'] ?? '';
            $datos['doctor_id'] = $orig['doctor_id'] ? (int) $orig['doctor_id'] : '';
            $datos['tratamiento_id'] = $orig['tratamiento_id'] ? (int) $orig['tratamiento_id'] : '';
            $datos['motivo'] = trim((string) ($orig['motivo'] ?? ''));
            $datos['forma_pago'] = '';
            $datos['notas'] = '';
            $datos['estado'] = 'pendiente';
            $prox = strtotime('+7 days');
            $datos['fecha'] = date('Y-m-d', $prox);
            $datos['hora'] = date('H:i', strtotime($orig['hora']));
        }
    } elseif ($desdePaciente > 0) {
        $stmtP = $pdo->prepare('SELECT * FROM pacientes WHERE id = ?');
        $stmtP->execute([$desdePaciente]);
        $pp = $stmtP->fetch();
        if ($pp) {
            $datos['paciente'] = $pp['nombre'];
            $datos['dni'] = $pp['dni'] ?? '';
            $datos['telefono'] = $pp['telefono'] ?? '';
            $datos['email'] = $pp['email'] ?? '';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'paciente'       => trim($_POST['paciente'] ?? ''),
        'dni'            => trim($_POST['dni'] ?? ''),
        'telefono'       => trim($_POST['telefono'] ?? ''),
        'email'          => trim($_POST['email'] ?? ''),
        'fecha'          => $_POST['fecha'] ?? '',
        'hora'           => $_POST['hora'] ?? '',
        'doctor_id'      => $_POST['doctor_id'] !== '' ? (int)$_POST['doctor_id'] : null,
        'tratamiento_id' => $_POST['tratamiento_id'] !== '' ? (int)$_POST['tratamiento_id'] : null,
        'motivo'         => trim($_POST['motivo'] ?? ''),
        'estado'         => $_POST['estado'] ?? 'pendiente',
        'forma_pago'     => trim($_POST['forma_pago'] ?? ''),
        'notas'          => trim($_POST['notas'] ?? ''),
    ];

    if ($datos['paciente'] === '') {
        $errores[] = 'El nombre del paciente es obligatorio.';
    }
    if ($datos['fecha'] === '') {
        $errores[] = 'La fecha es obligatoria.';
    }
    if ($datos['hora'] === '') {
        $errores[] = 'La hora es obligatoria.';
    }
    if ($datos['doctor_id'] === null) {
        $errores[] = 'Debe seleccionar un doctor o higienista.';
    }
    if ($datos['email'] !== '' && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no tiene un formato válido.';
    }

    if ($datos['doctor_id'] !== null && $datos['fecha'] !== '' && $datos['hora'] !== '') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE doctor_id = ? AND fecha = ? AND hora = ? AND estado IN ('pendiente','confirmada')");
        $stmt->execute([$datos['doctor_id'], $datos['fecha'], $datos['hora']]);
        if ((int)$stmt->fetchColumn() > 0) {
            $stmtDoc2 = $pdo->prepare("SELECT nombre FROM doctores WHERE id = ?");
            $stmtDoc2->execute([$datos['doctor_id']]);
            $docNombre = $stmtDoc2->fetchColumn();
            $errores[] = "El/La $docNombre ya tiene una cita el $datos[fecha] a las $datos[hora].";
        }
    }

    if (empty($errores)) {
        $stmtP = $pdo->prepare("SELECT id FROM pacientes WHERE nombre = ?");
        $stmtP->execute([$datos['paciente']]);
        $pacienteExistente = $stmtP->fetch();

        if ($pacienteExistente) {
            $paciente_id = $pacienteExistente['id'];
            $pdo->prepare("UPDATE pacientes SET dni = ?, telefono = ?, email = ? WHERE id = ?")
                ->execute([$datos['dni'], $datos['telefono'], $datos['email'], $paciente_id]);
        } else {
            $pdo->prepare("INSERT INTO pacientes (nombre, dni, telefono, email) VALUES (?, ?, ?, ?)")
                ->execute([$datos['paciente'], $datos['dni'], $datos['telefono'], $datos['email']]);
            $paciente_id = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("INSERT INTO citas (paciente, paciente_id, dni, telefono, email, fecha, hora, doctor_id, motivo, tratamiento_id, estado, forma_pago, notas)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $datos['paciente'],
            $paciente_id,
            $datos['dni'],
            $datos['telefono'],
            $datos['email'],
            $datos['fecha'],
            $datos['hora'],
            $datos['doctor_id'],
            $datos['motivo'],
            $datos['tratamiento_id'],
            $datos['estado'],
            $datos['forma_pago'],
            $datos['notas'],
        ]);

        header('Location: index.php?msg=creada');
        registrarAccion($pdo, 'crear', 'citas', null, "Nueva cita para {$datos['paciente']}");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DenTera - Nueva Cita</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<?php
$header_tagline = 'Nueva cita';
$header_nav = 'page';
require __DIR__ . '/inc/header.php';
?>

<main class="container">
    <div class="form-page-wide">
        <?php if (!empty($errores)): ?>
            <?php foreach ($errores as $e): ?>
                <div class="message message-error">&#x26A0; <?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-grid">
                <!-- Paciente -->
                <div class="form-section">
                    <div class="section-title">
                        <span class="section-icon">&#x1F464;</span>
                        <h3>Datos del paciente</h3>
                    </div>
                    <div class="section-body">
                        <div class="form-group full">
                            <label for="paciente">Nombre completo <span class="required">*</span></label>
                            <input type="text" id="paciente" name="paciente" required
                                   value="<?= htmlspecialchars($datos['paciente']) ?>"
                                   placeholder="Nombre y apellidos">
                        </div>
                        <div class="form-row-3">
                            <div class="form-group">
                                <label for="dni">DNI / NIE</label>
                                <input type="text" id="dni" name="dni"
                                       value="<?= htmlspecialchars($datos['dni']) ?>"
                                       placeholder="12345678A">
                            </div>
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono"
                                       value="<?= htmlspecialchars($datos['telefono']) ?>"
                                       placeholder="612 345 678">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email"
                                       value="<?= htmlspecialchars($datos['email']) ?>"
                                       placeholder="email@ejemplo.com">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cita -->
                <div class="form-section">
                    <div class="section-title">
                        <span class="section-icon">&#x1F4C5;</span>
                        <h3>Fecha, profesional y motivo</h3>
                    </div>
                    <div class="section-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fecha">Fecha <span class="required">*</span></label>
                                <input type="date" id="fecha" name="fecha" required
                                       value="<?= htmlspecialchars($datos['fecha']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="hora">Hora <span class="required">*</span></label>
                                <input type="time" id="hora" name="hora" required
                                       value="<?= htmlspecialchars($datos['hora']) ?>">
                            </div>
                        </div>
                        <div class="form-group full">
                            <label for="doctor_id">Doctor / Higienista <span class="required">*</span></label>
                            <select id="doctor_id" name="doctor_id" required>
                                <option value="">-- Seleccionar profesional --</option>
                                <?php foreach ($doctores as $d): ?>
                                    <option value="<?= $d['id'] ?>"
                                            <?= $datos['doctor_id'] == $d['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['nombre']) ?>
                                        <?= $d['especialidad'] ? ' — ' . htmlspecialchars($d['especialidad']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group full">
                            <label for="motivo">Motivo de la consulta</label>
                            <input type="text" id="motivo" name="motivo"
                                   value="<?= htmlspecialchars($datos['motivo']) ?>"
                                   placeholder="Ej: Dolor en muela, revisión general...">
                        </div>
                    </div>
                </div>

                <!-- Tratamiento -->
                <div class="form-section">
                    <div class="section-title">
                        <span class="section-icon">&#x1F48A;</span>
                        <h3>Tratamiento y pago</h3>
                    </div>
                    <div class="section-body">
                        <div class="form-group full">
                            <label for="tratamiento_id">Tratamiento</label>
                            <select id="tratamiento_id" name="tratamiento_id">
                                <option value="">-- Sin asignar --</option>
                                <?php foreach ($tratamientos as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                            <?= $datos['tratamiento_id'] == $t['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['nombre']) ?>
                                        <?= $t['precio'] > 0 ? ' — ' . number_format($t['precio'], 2) . ' €' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado">
                                    <option value="pendiente"  <?= $datos['estado'] === 'pendiente'  ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="confirmada" <?= $datos['estado'] === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                                    <option value="cancelada"  <?= $datos['estado'] === 'cancelada'  ? 'selected' : '' ?>>Cancelada</option>
                                    <option value="completada" <?= $datos['estado'] === 'completada' ? 'selected' : '' ?>>Completada</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="forma_pago">Forma de pago</label>
                                <select id="forma_pago" name="forma_pago">
                                    <option value="">-- Sin definir --</option>
                                    <option value="efectivo" <?= $datos['forma_pago'] === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                                    <option value="tarjeta" <?= $datos['forma_pago'] === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                                    <option value="transferencia" <?= $datos['forma_pago'] === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                                    <option value="bizum" <?= $datos['forma_pago'] === 'bizum' ? 'selected' : '' ?>>Bizum</option>
                                    <option value="seguro" <?= $datos['forma_pago'] === 'seguro' ? 'selected' : '' ?>>Seguro médico</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                <div class="form-section">
                    <div class="section-title">
                        <span class="section-icon">&#x1F4DD;</span>
                        <h3>Notas internas</h3>
                    </div>
                    <div class="section-body">
                        <div class="form-group full">
                            <label for="notas">Notas</label>
                            <textarea id="notas" name="notas" rows="3"
                                      placeholder="Alergias, observaciones, historial relevante..."><?= htmlspecialchars($datos['notas']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions-bar">
                <a href="index.php" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-lg">Guardar Cita</button>
            </div>
        </form>
    </div>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>

</body>
</html>
