<?php
$highestWorkload = max(1, ...array_column($managerWorkload, 'open_count'));
?>
<section id="equipo" class="manager-workload rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="equipo-titulo">
  <div class="manager-workload__heading">
    <div class="manager-workload__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-4 2-6 6-6s6 2 6 6M15 15c4 0 6 2 6 5"/></svg><div><h2 id="equipo-titulo">Distribución del trabajo</h2><p>Carga de solicitudes abiertas por personal del CSI.</p></div></div>
    <a href="/csi/requests?assignment=assigned">Ver todas las asignaciones <span aria-hidden="true">›</span></a>
  </div>
  <?php if ($managerWorkload === []): ?><p class="manager-workload__empty">No hay personal técnico activo.</p><?php else: ?>
  <div class="manager-workload__table-wrap"><table class="manager-workload__table"><thead><tr><th scope="col">Técnico</th><th scope="col">Asignadas</th><th scope="col">Carga relativa</th></tr></thead><tbody>
    <?php foreach ($managerWorkload as $technician): ?>
      <?php $percentage = (int) round(100 * $technician['open_count'] / $highestWorkload); ?>
      <tr>
        <th scope="row"><div class="manager-workload__person"><span class="manager-workload__avatar" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3"/><path d="M5 20c0-4 3-6 7-6s7 2 7 6"/></svg></span><span class="manager-workload__person-text"><strong><?= $e(\App\Presentation\DisplayName::withoutDemoMarker($technician['name'])) ?></strong><span class="manager-workload__links"><a href="/csi/requests?technician=<?= (int) $technician['id'] ?>">Ver casos</a><?php if ($user->can('request.assign') && $technician['cases'] !== []): ?><a href="/csi/requests/<?= rawurlencode($technician['cases'][0]['public_code']) ?>#asignar-responsable">Reasignar</a><?php endif; ?></span></span></div></th>
        <td data-label="Asignadas"><strong><?= (int) $technician['open_count'] ?></strong></td>
        <td data-label="Carga relativa"><div class="manager-workload__load"><progress value="<?= $percentage ?>" max="100" aria-label="Carga relativa de <?= $e(\App\Presentation\DisplayName::withoutDemoMarker($technician['name'])) ?>: <?= $percentage ?> %"></progress><strong><?= $percentage ?>%</strong></div></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <p class="manager-workload__note">Asignadas: solicitudes abiertas con responsable activo. La barra compara cada carga con la mayor carga actual del equipo (100%).</p>
  <?php endif; ?>
</section>
