<?php
require_once 'config.php';
require_once 'sesion.php';
requerirLogin();

$busqueda = trim($_GET['q'] ?? '');

$sql = "SELECT p.id, p.nombre, p.dni, p.telefono, p.email, p.creado,
               COUNT(c.id) AS num_citas
        FROM pacientes p
        LEFT JOIN citas c ON c.paciente_id = p.id";
$params = [];
if ($busqueda !== '') {
    $sql .= " WHERE (p.nombre LIKE ? OR p.dni LIKE ? OR p.telefono LIKE ? OR p.email LIKE ?)";
    $kw = "%{$busqueda}%";
    $params = [$kw, $kw, $kw, $kw];
}
$sql .= " GROUP BY p.id
          ORDER BY p.nombre ASC
          LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pacientes = $stmt->fetchAll();

function iniciales_pac($nombre) {
    $parts = explode(' ', trim($nombre));
    if (count($parts) >= 2) {
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }
    return strtoupper(mb_substr($nombre, 0, 2));
}

function colorAvatar_pac($nombre) {
    $colors = ['#6366f1','#8b5cf6','#ec4899','#f43f5e','#f97316','#eab308','#22c55e','#14b8a6','#06b6d4','#3b82f6'];
    $idx = 0;
    foreach (str_split($nombre) as $c) {
        $idx += ord($c);
    }
    return $colors[$idx % count($colors)];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DenTera - Pacientes</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<?php
$header_tagline = 'Directorio de pacientes';
$header_nav = 'page';
require __DIR__ . '/inc/header.php';
?>

<main class="container">
    <div class="page-intro">
        <div class="page-intro-text">
            <h2>Pacientes</h2>
            <p>Busca por nombre, DNI, teléfono o email. Accede al historial o agenda una nueva cita.</p>
        </div>
        <div class="page-intro-controls">
            <a href="crear.php" class="btn-header-cta">+ Nueva cita</a>
        </div>
    </div>

    <div class="toolbar">
        <form class="search-box" method="GET" action="pacientes.php" autocomplete="off">
            <input type="search" name="q" placeholder="Buscar paciente…"
                   value="<?= htmlspecialchars($busqueda) ?>" style="min-width:220px;">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="pacientes.php" class="btn btn-outline">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrapper">
        <?php if (count($pacientes) === 0): ?>
            <div class="empty">
                <div class="empty-icon">&#x1F465;</div>
                <h3>No hay pacientes</h3>
                <p><?= $busqueda !== '' ? 'Prueba con otros términos de búsqueda.' : 'Los pacientes aparecen al registrar la primera cita.' ?></p>
            </div>
        <?php else: ?>
            <table class="pacientes-table">
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th class="hide-mobile">Contacto</th>
                        <th class="hide-mobile">Alta</th>
                        <th>Citas</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pacientes as $p): ?>
                    <tr>
                        <td>
                            <div class="patient-cell">
                                <div class="patient-avatar" style="background:<?= colorAvatar_pac($p['nombre']) ?>">
                                    <?= iniciales_pac($p['nombre']) ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                    <?php if ($p['dni']): ?>
                                        <div class="patient-dni"><?= htmlspecialchars($p['dni']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="hide-mobile">
                            <div class="contact-cell">
                                <?php if ($p['telefono']): ?>
                                    <a href="tel:<?= preg_replace('/\s+/', '', $p['telefono']) ?>" class="contact-item"><?= htmlspecialchars($p['telefono']) ?></a>
                                <?php endif; ?>
                                <?php if ($p['email']): ?>
                                    <a href="mailto:<?= htmlspecialchars($p['email']) ?>" class="contact-item"><?= htmlspecialchars($p['email']) ?></a>
                                <?php endif; ?>
                                <?php if ($p['telefono'] === '' && $p['email'] === ''): ?>
                                    <span style="color:var(--gray-400);">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="hide-mobile"><?= date('d/m/Y', strtotime($p['creado'])) ?></td>
                        <td><span class="pacientes-num-citas"><?= (int) $p['num_citas'] ?></span></td>
                        <td>
                            <div class="actions">
                                <a href="paciente.php?id=<?= (int) $p['id'] ?>" class="btn btn-outline btn-sm">Historial</a>
                                <a href="crear.php?desde_paciente=<?= (int) $p['id'] ?>" class="btn btn-primary btn-sm">+ Cita</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($pacientes) >= 500): ?>
                <p class="pacientes-limite-aviso">Se muestran los primeros 500 resultados. Refina la búsqueda si no encuentras a alguien.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>

<?php
$footer_subtitulo = 'Pacientes';
require __DIR__ . '/inc/footer.php';
?>

</body>
</html>
