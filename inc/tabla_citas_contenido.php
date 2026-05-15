<?php
/**
 * Fragmento HTML del bloque .table-wrapper (tabla o estado vacío).
 * Variables: $citas, $filtrosActivos, $orden, $orden_dir, $get
 */
?>
        <?php if (count($citas) === 0): ?>
            <div class="empty">
                <div class="empty-icon">&#x1F4C5;</div>
                <h3>No hay citas que mostrar</h3>
                <p><?= $filtrosActivos ? 'Prueba a modificar los filtros de búsqueda.' : 'Haz clic en "Nueva Cita" para agregar la primera.' ?></p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <?php
                        $cols = [
                            ['key' => '_drag', 'label' => '', 'class' => 'citas-drag-th'],
                            ['key' => 'paciente', 'label' => 'Paciente', 'class' => ''],
                            ['key' => 'contacto', 'label' => 'Contacto', 'class' => 'hide-mobile'],
                            ['key' => 'fecha', 'label' => 'Fecha', 'class' => ''],
                            ['key' => 'hora', 'label' => 'Hora', 'class' => ''],
                            ['key' => 'tratamiento', 'label' => 'Tratamiento', 'class' => 'hide-mobile'],
                            ['key' => 'doctor', 'label' => 'Dentista', 'class' => 'hide-mobile'],
                            ['key' => 'motivo', 'label' => 'Motivo', 'class' => ''],
                            ['key' => '', 'label' => 'Estado', 'class' => ''],
                            ['key' => '', 'label' => '', 'class' => ''],
                        ];
                        foreach ($cols as $col):
                            if ($col['key'] === '_drag'):
                        ?>
                            <th class="<?= $col['class'] ?>" scope="col" aria-label="Reordenar filas"></th>
                        <?php elseif ($col['key'] === '' || $col['key'] === 'contacto' || $col['key'] === 'tratamiento'):
                        ?>
                            <th class="<?= $col['class'] ?>"><?= $col['label'] ?></th>
                        <?php else:
                            $s = sortUrl($col['key'], $orden, $orden_dir, $get);
                        ?>
                            <th class="<?= $col['class'] ?>">
                                <a href="<?= $s['url'] ?>" class="sort-link"><?= $col['label'] ?><?= $s['arrow'] ?></a>
                            </th>
                        <?php endif; endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($citas as $c): ?>
                    <tr data-cita-id="<?= (int) $c['id'] ?>" data-fecha="<?= htmlspecialchars($c['fecha'], ENT_QUOTES, 'UTF-8') ?>">
                        <td class="<?= $orden === 'fecha' ? 'citas-drag-cell' : 'citas-drag-spacer' ?>">
                            <?php if ($orden === 'fecha'): ?>
                            <span class="citas-drag-handle" title="Arrastrar para ordenar (solo entre citas del mismo día)">&#8942;&#8942;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="patient-cell">
                                <div class="patient-avatar" style="background:<?= colorAvatar($c['paciente']) ?>">
                                    <?= iniciales($c['paciente']) ?>
                                </div>
                                <div>
                                    <a href="paciente.php?id=<?= $c['paciente_id'] ?: '#' ?>" class="patient-name-link"><?= htmlspecialchars($c['paciente']) ?></a>
                                    <?php if ($c['dni']): ?>
                                        <div class="patient-dni"><?= htmlspecialchars($c['dni']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="hide-mobile">
                            <div class="contact-cell">
                                <?php if ($c['telefono']): ?>
                                    <span class="contact-item"><span class="contact-icon">&#x1F4DE;</span> <?= htmlspecialchars($c['telefono']) ?></span>
                                <?php endif; ?>
                                <?php if ($c['email']): ?>
                                    <span class="contact-item"><span class="contact-icon">&#x2709;</span> <?= htmlspecialchars($c['email']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="date-cell">
                                <span class="date-main"><?= date('d/m/Y', strtotime($c['fecha'])) ?></span>
                                <span class="date-day"><?= diaSemana($c['fecha']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="time-badge">&#x1F550; <?= date('H:i', strtotime($c['hora'])) ?></span>
                        </td>
                        <td class="hide-mobile">
                            <?php if ($c['tratamiento_nombre']): ?>
                                <span class="treatment-badge"><?= htmlspecialchars($c['tratamiento_nombre']) ?></span>
                            <?php else: ?>
                                <span style="color:var(--gray-400);font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="hide-mobile">
                            <?php if ($c['doctor_nombre']): ?>
                                <span><?= htmlspecialchars($c['doctor_nombre']) ?></span>
                                <?php if ($c['doctor_especialidad']): ?>
                                    <div style="font-size:0.75rem;color:var(--gray-400);"><?= htmlspecialchars($c['doctor_especialidad']) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:var(--gray-400);font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(mb_strlen($c['motivo']) > 35 ? mb_substr($c['motivo'], 0, 35) . '...' : $c['motivo']) ?></td>
                        <td>
                            <span class="badge badge-<?= $c['estado'] ?>">
                                <span class="badge-dot"></span>
                                <?= traduccion($c['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="editar.php?id=<?= $c['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="duplicar_cita.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-sm" title="Copiar datos en una cita nueva">Duplicar</a>
                                <a href="eliminar.php?id=<?= $c['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('¿Eliminar la cita de <?= htmlspecialchars(addslashes($c['paciente'])) ?>?')">Eliminar</a>
                                <a href="presupuesto.php?paciente=<?= urlencode($c['paciente']) ?>" class="btn btn-primary btn-sm" title="Ver presupuesto">&#x1F4B0;</a>
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
