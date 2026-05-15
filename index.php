<?php
require_once 'config.php';
require_once 'sesion.php';
requerirLogin();

require_once __DIR__ . '/inc/citas_vista_helpers.php';
require_once __DIR__ . '/inc/citas_filtro.php';

$busqueda   = trim($_GET['busqueda'] ?? '');
$estado     = $_GET['estado'] ?? '';
$fecha      = $_GET['fecha'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$mes_cal    = (int)($_GET['mes'] ?? date('n'));
$anio_cal   = (int)($_GET['anio'] ?? date('Y'));
$ver        = $_GET['ver'] ?? 'lista';

$get = $_GET;
[$sql, $params, $orden, $orden_dir] = citas_filtro_sql($get);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$citas = $stmt->fetchAll();

$stmtTotal = $pdo->query("SELECT COUNT(*) as total FROM citas");
$total = $stmtTotal->fetch()['total'];

$stmtPend = $pdo->query("SELECT COUNT(*) FROM citas WHERE estado = 'pendiente'");
$pendientes = $stmtPend->fetchColumn();

$stmtHoy = $pdo->query("SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()");
$hoy = $stmtHoy->fetchColumn();

$stmtConf = $pdo->query("SELECT COUNT(*) FROM citas WHERE estado = 'confirmada'");
$confirmadas = $stmtConf->fetchColumn();

$stmtProx = $pdo->query("SELECT c.id, c.fecha, c.hora, c.paciente, c.estado, d.nombre AS doctor_nombre
    FROM citas c
    LEFT JOIN doctores d ON c.doctor_id = d.id
    WHERE c.fecha >= CURDATE() AND c.fecha <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
    AND c.estado IN ('pendiente', 'confirmada')
    ORDER BY c.fecha ASC, c.hora ASC
    LIMIT 12");
$proximasCitas = $stmtProx->fetchAll();

/* Calendar data */
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$diasSemana = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];

$primerDia = mktime(0, 0, 0, $mes_cal, 1, $anio_cal);
$ultimoDia = mktime(0, 0, 0, $mes_cal + 1, 0, $anio_cal);
$diasEnMes = (int)date('t', $primerDia);
$diaSemanaInicio = (int)date('N', $primerDia) - 1;

$mesAnterior = $mes_cal - 1;
$anioAnterior = $anio_cal;
if ($mesAnterior < 1) { $mesAnterior = 12; $anioAnterior--; }

$mesSiguiente = $mes_cal + 1;
$anioSiguiente = $anio_cal;
if ($mesSiguiente > 12) { $mesSiguiente = 1; $anioSiguiente++; }

$stmtDias = $pdo->prepare("SELECT fecha, COUNT(*) as total FROM citas WHERE fecha BETWEEN ? AND ? GROUP BY fecha");
$desde = date('Y-m-d', $primerDia);
$hasta = date('Y-m-d', $ultimoDia);
$stmtDias->execute([$desde, $hasta]);
$citasPorDia = [];
while ($row = $stmtDias->fetch()) {
    $citasPorDia[$row['fecha']] = (int)$row['total'];
}

$toastMsg = '';
if (isset($_GET['msg'])) {
    $msgs = [
        'creada'     => 'Cita creada correctamente',
        'actualizada'=> 'Cita actualizada correctamente',
        'eliminada'  => 'Cita eliminada correctamente',
    ];
    $toastMsg = $msgs[$_GET['msg']] ?? '';
}

$filtrosActivos = $busqueda !== '' || $estado !== '' || $fecha !== '' || $fecha_desde !== '' || $fecha_hasta !== '';

$hoyStr = date('Y-m-d');
$queryHoy = array_merge($_GET, ['fecha_desde' => $hoyStr, 'fecha_hasta' => $hoyStr, 'fecha' => '', 'ver' => 'lista']);
$linkCitasHoy = '?' . http_build_query($queryHoy);
$linkExportCsv = 'export_citas_csv.php?' . http_build_query($_GET);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DenTera - Gestión de Citas</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" href="img/logo.png" type="image/png">
</head>
<body>

<?php
$header_tagline = 'Gestión de citas';
$header_nav = 'dashboard';
require __DIR__ . '/inc/header.php';
?>

<main class="container">

    <?php if ($toastMsg): ?>
    <div class="toast">
        <span class="toast-icon">&#x2705;</span>
        <?= htmlspecialchars($toastMsg) ?>
    </div>
    <?php endif; ?>

    <div class="page-intro">
        <div class="page-intro-text">
            <h2>Agenda</h2>
            <p>Consulta y filtra citas, cambia entre lista y calendario, y registra nuevas visitas en un solo lugar.</p>
        </div>
        <div class="page-intro-controls">
            <div class="view-toggle" role="group" aria-label="Vista">
                <a href="?<?= http_build_query(array_merge($_GET, ['ver' => 'lista'])) ?>" class="toggle-btn <?= ($ver === 'lista' ? 'active' : '') ?>">
                    &#x1F4CB; Lista
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['ver' => 'calendario'])) ?>" class="toggle-btn <?= ($ver === 'calendario' ? 'active' : '') ?>">
                    &#x1F4C5; Calendario
                </a>
            </div>
            <a href="crear.php" class="btn-header-cta">+ Nueva cita</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card blue">
            <div class="stat-icon">&#x1F4CB;</div>
            <div class="stat-info">
                <div class="stat-num"><?= $total ?></div>
                <div class="stat-label">Total de citas</div>
            </div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon">&#x23F3;</div>
            <div class="stat-info">
                <div class="stat-num"><?= $pendientes ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">&#x2705;</div>
            <div class="stat-info">
                <div class="stat-num"><?= $confirmadas ?></div>
                <div class="stat-label">Confirmadas</div>
            </div>
        </div>
        <div class="stat-card cyan">
            <div class="stat-icon">&#x1F4C5;</div>
            <div class="stat-info">
                <div class="stat-num"><?= $hoy ?></div>
                <div class="stat-label">Citas hoy</div>
            </div>
        </div>
    </div>

    <?php if (count($proximasCitas) > 0): ?>
    <div class="proximas-citas-card form-card">
        <div class="form-header">
            <div class="form-header-icon">&#x1F4C5;</div>
            <h2>Próximas citas (14 días)</h2>
        </div>
        <div class="proximas-citas-body">
            <ul class="proximas-citas-list">
                <?php foreach ($proximasCitas as $pc): ?>
                <li>
                    <a href="editar.php?id=<?= (int) $pc['id'] ?>" class="proxima-cita-link">
                        <span class="proxima-cita-fecha"><?= date('d/m', strtotime($pc['fecha'])) ?> <?= date('H:i', strtotime($pc['hora'])) ?></span>
                        <span class="proxima-cita-pac"><?= htmlspecialchars($pc['paciente']) ?></span>
                        <span class="proxima-cita-doc"><?= htmlspecialchars($pc['doctor_nombre'] ?: '—') ?></span>
                        <span class="badge badge-<?= $pc['estado'] ?>"><?= traduccion($pc['estado']) ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="toolbar">
        <form class="search-box" id="form-filtros-agenda" method="GET" autocomplete="off">
            <input type="hidden" name="ver" value="<?= htmlspecialchars($ver) ?>">
            <input type="text" name="busqueda" placeholder="Nombre, DNI, contacto o fecha (ej. 15/03/2026)…"
                   value="<?= htmlspecialchars($busqueda) ?>" id="input-busqueda-agenda"
                   spellcheck="false">
            <select name="estado">
                <option value="">Todos los estados</option>
                <option value="pendiente"  <?= $estado === 'pendiente'  ? 'selected' : '' ?>>Pendiente</option>
                <option value="confirmada" <?= $estado === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                <option value="cancelada"  <?= $estado === 'cancelada'  ? 'selected' : '' ?>>Cancelada</option>
                <option value="completada" <?= $estado === 'completada' ? 'selected' : '' ?>>Completada</option>
            </select>
            <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>" title="Desde">
            <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>" title="Hasta">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <?php if ($filtrosActivos): ?>
                <a href="?ver=<?= $ver ?>" class="btn btn-outline">Limpiar</a>
            <?php endif; ?>
        </form>
        <div class="toolbar-extras">
            <a href="<?= htmlspecialchars($linkCitasHoy, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline btn-sm">Citas de hoy</a>
            <a href="<?= htmlspecialchars($linkExportCsv, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline btn-sm">Exportar CSV</a>
        </div>
    </div>

    <?php if ($ver === 'calendario'): ?>
    <!-- Calendar -->
    <div class="calendar-card">
        <div class="calendar-header">
            <a href="?<?= http_build_query(array_merge($_GET, ['mes' => $mesAnterior, 'anio' => $anioAnterior])) ?>" class="cal-nav-btn">&#x25C0;</a>
            <h2><?= $meses[$mes_cal] ?> <?= $anio_cal ?></h2>
            <a href="?<?= http_build_query(array_merge($_GET, ['mes' => $mesSiguiente, 'anio' => $anioSiguiente])) ?>" class="cal-nav-btn">&#x25B6;</a>
        </div>
        <div class="calendar-grid">
            <?php foreach ($diasSemana as $d): ?>
                <div class="cal-day-header"><?= $d ?></div>
            <?php endforeach; ?>

            <?php for ($i = 0; $i < $diaSemanaInicio; $i++): ?>
                <div class="cal-day cal-empty"></div>
            <?php endfor; ?>

            <?php for ($d = 1; $d <= $diasEnMes; $d++): ?>
                <?php
                $fechaDia = sprintf('%04d-%02d-%02d', $anio_cal, $mes_cal, $d);
                $numCitas = $citasPorDia[$fechaDia] ?? 0;
                $esHoy = ($fechaDia === date('Y-m-d'));
                $esSeleccionado = ($fechaDia === $fecha);
                $clases = 'cal-day';
                if ($esHoy) $clases .= ' cal-today';
                if ($esSeleccionado) $clases .= ' cal-selected';
                if ($numCitas > 0) $clases .= ' cal-has-citas';
                $href = http_build_query(array_merge($_GET, ['fecha' => $fechaDia, 'ver' => 'calendario']));
                ?>
                <a href="?<?= $href ?>" class="<?= $clases ?>" title="<?= $numCitas ?> cita(s)">
                    <span class="cal-num"><?= $d ?></span>
                    <?php if ($numCitas > 0): ?>
                        <span class="cal-dot"><?= $numCitas ?></span>
                    <?php endif; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <?php if ($fecha !== ''): ?>
    <div class="calendar-day-info">
        <div class="day-info-header">
            <h3>&#x1F4C5; Citas del <?= date('d/m/Y', strtotime($fecha)) ?> (<?= diaSemana($fecha) ?>)</h3>
            <a href="?<?= http_build_query(array_merge($_GET, ['fecha' => '', 'ver' => 'calendario'])) ?>" class="btn btn-outline btn-sm">Mostrar todas</a>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-wrapper" id="tabla-citas-agenda" data-orden-actual="<?= htmlspecialchars($orden, ENT_QUOTES, 'UTF-8') ?>">
        <?php require __DIR__ . '/inc/tabla_citas_contenido.php'; ?>
    </div>
</main>

<?php
$footer_subtitulo = 'Sistema de Gestión de Citas';
require __DIR__ . '/inc/footer.php';
$pathBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$dentistaApiTabla = $pathBase . '/api_tabla_citas.php';
$dentistaApiReordenar = $pathBase . '/api_reordenar_citas.php';
$jsBase = $pathBase . '/js/';
?>
<script>
window.DENTISTA_API_TABLA_CITAS = <?= json_encode($dentistaApiTabla, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
window.DENTISTA_API_REORDENAR_CITAS = <?= json_encode($dentistaApiReordenar, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
</script>
<script src="<?= htmlspecialchars($jsBase . 'Sortable.min.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
<script src="<?= htmlspecialchars($jsBase . 'citas-sortable.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
<script src="<?= htmlspecialchars($jsBase . 'agenda-live.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>
