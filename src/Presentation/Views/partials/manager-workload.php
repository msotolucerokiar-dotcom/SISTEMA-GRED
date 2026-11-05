<section id="equipo" class="manager-workload rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="equipo-titulo">
  <div class="manager-workload__heading">
    <div class="manager-workload__title"><span class="manager-workload__title-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="currentColor"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M1 20c0-4 2.5-6 7-6s7 2 7 6H1Zm14-5c4 0 7 1.5 7 5h-5c0-2.1-.7-3.7-2-5Z"/></svg></span><div><h2 id="equipo-titulo">Distribución del trabajo</h2><p>Carga de solicitudes por integrante del CSI.</p></div></div>
    <a href="/csi/requests?assignment=assigned" class="manager-workload__all">Ver detalle <span aria-hidden="true">→</span></a>
  </div>
  <div class="manager-workload__mobile-legend"><span><i class="manager-workload__dot manager-workload__dot--pending" aria-hidden="true"></i>Pendientes</span><span><i class="manager-workload__dot manager-workload__dot--progress" aria-hidden="true"></i>En atención</span><span><i class="manager-workload__dot manager-workload__dot--resolved" aria-hidden="true"></i>Solucionadas</span></div>
  <?php if ($managerWorkload === []): ?><p class="manager-workload__empty">No hay personal CSI activo.</p><?php else: ?>
  <div class="manager-workload__table-wrap"><table class="manager-workload__table"><thead><tr><th scope="col">Personal</th><th scope="col"><span class="manager-workload__head-states"><span><i class="manager-workload__dot manager-workload__dot--pending" aria-hidden="true"></i>Pendientes</span><span><i class="manager-workload__dot manager-workload__dot--progress" aria-hidden="true"></i>En atención</span><span><i class="manager-workload__dot manager-workload__dot--resolved" aria-hidden="true"></i>Solucionadas</span></span></th><th scope="col">Total</th></tr></thead><tbody>
    <?php foreach ($managerWorkload as $technician): ?>
      <?php
        $pending = (int) $technician['pending_count'];
        $inProgress = (int) $technician['in_progress_count'];
        $resolved = (int) $technician['resolved_count'];
        $total = $pending + $inProgress + $resolved;
        $pendingPercent = $total > 0 ? 100 * $pending / $total : 0;
        $inProgressPercent = $total > 0 ? 100 * $inProgress / $total : 0;
        $resolvedPercent = $total > 0 ? 100 * $resolved / $total : 0;
        $name = \App\Presentation\DisplayName::withoutDemoMarker($technician['name']);
      ?>
      <tr>
        <th scope="row"><div class="manager-workload__person"><span class="manager-workload__avatar" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3"/><path d="M5 20c0-4 3-6 7-6s7 2 7 6"/></svg></span><a class="manager-workload__person-link" href="/csi/requests?technician=<?= (int) $technician['id'] ?>"><?= $e($name) ?></a></div></th>
        <td><div class="manager-workload__bar" role="img" aria-label="<?= $e($name) ?>: <?= $pending ?> pendientes, <?= $inProgress ?> en atención y <?= $resolved ?> solucionadas"><span class="manager-workload__bar-pending" style="width:<?= $pendingPercent ?>%"<?= $pending === 0 ? ' aria-hidden="true"' : '' ?>><?= $pending > 0 ? $pending : '' ?></span><span class="manager-workload__bar-progress" style="width:<?= $inProgressPercent ?>%"<?= $inProgress === 0 ? ' aria-hidden="true"' : '' ?>><?= $inProgress > 0 ? $inProgress : '' ?></span><span class="manager-workload__bar-resolved" style="width:<?= $resolvedPercent ?>%"<?= $resolved === 0 ? ' aria-hidden="true"' : '' ?>><?= $resolved > 0 ? $resolved : '' ?></span><?php if ($total === 0): ?><span class="manager-workload__bar-empty">—</span><?php endif; ?></div></td>
        <td><div class="manager-workload__actions"><strong class="manager-workload__total" aria-label="<?= $total ?> solicitudes asignadas"><?= $total ?></strong><details class="manager-workload__more" data-workload-menu><summary aria-label="Acciones para <?= $e($name) ?>" title="Acciones para <?= $e($name) ?>"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg></summary><div class="manager-workload__more-menu"><a href="/csi/requests?technician=<?= (int) $technician['id'] ?>&amp;open=1">Ver casos abiertos</a><?php if ($user->can('request.assign') && $technician['cases'] !== []): ?><a href="/csi/requests/<?= rawurlencode($technician['cases'][0]['public_code']) ?>#asignar-responsable">Reasignar una solicitud</a><?php else: ?><span>No hay casos para reasignar</span><?php endif; ?></div></details></div></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>
