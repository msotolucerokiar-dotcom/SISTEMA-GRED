<section id="equipo" class="manager-workload manager-workload--overview rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="equipo-titulo">
  <div class="manager-workload__heading"><div class="manager-workload__title"><span class="manager-section-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-4 2-6 6-6s6 2 6 6M15 15c4 0 6 2 6 5"/></svg></span><div><h2 id="equipo-titulo">Carga de trabajo del equipo</h2></div></div></div>
  <div class="manager-workload__overview-legend"><span><i class="manager-workload__dot manager-workload__dot--pending" aria-hidden="true"></i>Pendientes</span><span><i class="manager-workload__dot manager-workload__dot--progress" aria-hidden="true"></i>En atenci&oacute;n</span><small>Solicitudes activas por responsable</small></div>
  <?php if ($managerWorkload === []): ?><p class="manager-workload__empty">No hay personal CSI activo.</p><?php else: ?>
  <div class="manager-workload__table-wrap"><table class="manager-workload__table"><thead class="sr-only"><tr><th scope="col">Personal</th><th scope="col">Carga activa</th><th scope="col">Acciones</th></tr></thead><tbody>
    <?php foreach ($managerWorkload as $technician): ?>
      <?php
        $pending = (int) $technician['pending_count'];
        $inProgress = (int) $technician['in_progress_count'];
        $total = $pending + $inProgress;
        $pendingPercent = $total > 0 ? 100 * $pending / $total : 0;
        $inProgressPercent = $total > 0 ? 100 * $inProgress / $total : 0;
        $name = \App\Presentation\DisplayName::withoutDemoMarker($technician['name']);
        $nameParts = preg_split('/\s+/u', trim($name)) ?: [];
        $initials = mb_strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[1] ?? '', 0, 1));
        $roleLabel = $technician['role_code'] === 'csi_manager' ? 'Responsable CSI' : 'Técnico CSI';
      ?>
      <tr><th scope="row"><div class="manager-workload__person"><span class="manager-workload__avatar" aria-hidden="true"><?= $e($initials) ?></span><span class="manager-workload__person-text"><a class="manager-workload__person-link" href="/csi/requests?technician=<?= (int) $technician['id'] ?>"><?= $e($name) ?></a><span class="manager-workload__role"><?= $e($roleLabel) ?></span></span></div></th>
      <td><div class="manager-workload__overview-load"><div class="manager-workload__bar" role="img" aria-label="<?= $e($name) ?>: <?= $pending ?> pendientes y <?= $inProgress ?> en atención"><span class="manager-workload__bar-pending" style="width:<?= $pendingPercent ?>%"></span><span class="manager-workload__bar-progress" style="width:<?= $inProgressPercent ?>%"></span><?php if ($total === 0): ?><span class="manager-workload__bar-empty"></span><?php endif; ?></div><small><?= $pending ?> pend. &middot; <?= $inProgress ?> en atenci&oacute;n</small></div></td>
      <td><div class="manager-workload__actions"><details class="manager-workload__more" data-workload-menu><summary aria-label="Acciones para <?= $e($name) ?>" title="Acciones para <?= $e($name) ?>"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg></summary><div class="manager-workload__more-menu"><a href="/csi/requests?technician=<?= (int) $technician['id'] ?>&amp;open=1">Ver casos abiertos</a><?php if ($user->can('request.assign') && $technician['cases'] !== []): ?><a href="/csi/requests/<?= rawurlencode($technician['cases'][0]['public_code']) ?>#asignar-responsable">Reasignar una solicitud</a><?php else: ?><span>No hay casos para reasignar</span><?php endif; ?></div></details></div></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
  <div class="manager-workload__footer"><a href="/csi/requests?assignment=assigned">Ver equipo &rsaquo;</a></div>
</section>
