<section id="equipo" class="manager-workload rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="equipo-titulo">
  <div class="manager-workload__heading">
    <div class="manager-workload__title"><span class="manager-workload__title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-4 2-6 6-6s6 2 6 6M15 15c4 0 6 2 6 5"/></svg></span><div><h2 id="equipo-titulo">Distribución del trabajo</h2><p>Carga de solicitudes por integrante del CSI</p></div></div>
    <a href="/csi/requests?assignment=assigned" class="manager-workload__all">Ver todas las asignaciones <span aria-hidden="true">→</span></a>
  </div>
  <?php if ($managerWorkload === []): ?><p class="manager-workload__empty">No hay personal CSI activo.</p><?php else: ?>
  <div class="manager-workload__table-wrap"><table class="manager-workload__table"><thead><tr><th scope="col">Personal</th><th scope="col">Carga por estado</th><th scope="col">Total y acciones</th></tr></thead><tbody>
    <?php foreach ($managerWorkload as $technician): ?>
      <?php
        $total = (int) $technician['open_count'];
        $pending = (int) $technician['pending_count'];
        $inProgress = (int) $technician['in_progress_count'];
        $pendingPercent = $total > 0 ? (int) round(100 * $pending / $total) : 0;
        $inProgressPercent = $total > 0 ? 100 - $pendingPercent : 0;
        $name = \App\Presentation\DisplayName::withoutDemoMarker($technician['name']);
        $isManager = $technician['role_code'] === 'csi_manager';
      ?>
      <tr>
        <th scope="row"><div class="manager-workload__person"><span class="manager-workload__avatar" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3"/><path d="M5 20c0-4 3-6 7-6s7 2 7 6"/></svg></span><span class="manager-workload__person-text"><strong><?= $e($name) ?></strong><span class="manager-workload__role"><?= $isManager ? 'Responsable CSI' : 'Técnico de soporte' ?></span></span></div></th>
        <td><div class="manager-workload__status"><div class="manager-workload__bar" role="img" aria-label="<?= $e($name) ?>: <?= $pending ?> pendientes y <?= $inProgress ?> en atención"><span class="manager-workload__bar-pending" style="width:<?= $pendingPercent ?>%"><?php if ($pendingPercent >= 18): ?><?= $pendingPercent ?>%<?php endif; ?></span><span class="manager-workload__bar-progress" style="width:<?= $inProgressPercent ?>%"><?php if ($inProgressPercent >= 18): ?><?= $inProgressPercent ?>%<?php endif; ?></span><?php if ($total === 0): ?><span class="manager-workload__bar-empty">0%</span><?php endif; ?></div><div class="manager-workload__counts"><span class="manager-workload__count-pending">Pendientes: <strong><?= $pending ?></strong></span><span class="manager-workload__count-progress">En atención: <strong><?= $inProgress ?></strong></span></div></div></td>
        <td><div class="manager-workload__actions"><strong class="manager-workload__total" aria-label="<?= $total ?> solicitudes abiertas asignadas"><?= $total ?></strong><a href="/csi/requests?technician=<?= (int) $technician['id'] ?>&amp;open=1" class="manager-workload__view"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>Ver casos</a><details class="manager-workload__more" data-workload-menu><summary aria-label="Más acciones para <?= $e($name) ?>" title="Más acciones"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg></summary><div class="manager-workload__more-menu"><?php if ($user->can('request.assign') && $technician['cases'] !== []): ?><a href="/csi/requests/<?= rawurlencode($technician['cases'][0]['public_code']) ?>#asignar-responsable">Reasignar una solicitud</a><?php else: ?><span>No hay casos para reasignar</span><?php endif; ?></div></details></div></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <div class="manager-workload__legend"><span><i class="manager-workload__swatch manager-workload__swatch--pending" aria-hidden="true"></i>Pendientes (sin iniciar)</span><span><i class="manager-workload__swatch manager-workload__swatch--progress" aria-hidden="true"></i>En atención (en proceso)</span><span><i class="manager-workload__swatch manager-workload__swatch--empty" aria-hidden="true"></i>Sin carga</span><p>ⓘ El total corresponde a solicitudes abiertas asignadas.</p></div>
  <?php endif; ?>
</section>
