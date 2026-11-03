<section id="equipo" class="manager-workload rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="equipo-titulo">
  <div class="manager-workload__heading">
    <div class="manager-workload__title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2 20c0-4 2-6 6-6s6 2 6 6M15 15c4 0 6 2 6 5"/></svg><div><h2 id="equipo-titulo">Carga de trabajo del personal CSI</h2><p>Consulta las asignaciones activas y equilibra la atención del equipo.</p></div></div>
    <a href="/csi/requests?assignment=assigned">Ver todas las asignaciones <span aria-hidden="true">›</span></a>
  </div>
  <?php if ($managerWorkload === []): ?><p class="manager-workload__empty">No hay personal técnico activo.</p><?php else: ?>
  <div class="manager-workload__cards">
    <?php foreach ($managerWorkload as $technician): ?>
      <?php $isManager = $technician['role_code'] === 'csi_manager'; ?>
      <article class="manager-workload-card <?= $isManager ? 'manager-workload-card--manager' : '' ?>">
        <div class="manager-workload-card__identity"><span class="manager-workload-card__avatar" aria-hidden="true"><?php if ($isManager): ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="m3 7 5 4 4-6 4 6 5-4-2 11H5L3 7Z"/><path d="M5 21h14"/></svg><?php else: ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="8" r="3"/><path d="M4 20c0-4 3-6 7-6s7 2 7 6M20 4v5m-2.5-2.5h5"/></svg><?php endif; ?></span><div class="manager-workload-card__person"><h3><?= $e(\App\Presentation\DisplayName::withoutDemoMarker($technician['name'])) ?></h3><p><?= $isManager ? 'Coordinación y atención directa' : 'Técnico de soporte' ?></p></div><span class="manager-workload-card__active" aria-label="Cuenta activa" title="Cuenta activa"></span></div>
        <dl class="manager-workload-card__metrics"><div><dt>Abiertas</dt><dd><?= (int) $technician['open_count'] ?></dd></div><div><dt>En atención</dt><dd><?= (int) $technician['in_progress_count'] ?></dd></div><div><dt>Prioridad alta</dt><dd class="manager-workload-card__high"><?= (int) $technician['high_priority_count'] ?></dd></div></dl>
        <div class="manager-workload-card__actions"><a href="/csi/requests?technician=<?= (int) $technician['id'] ?>" class="manager-workload-card__primary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h1M3 12h1M3 18h1"/></svg>Ver casos</a><?php if ($user->can('request.assign') && $technician['cases'] !== []): ?><a href="/csi/requests/<?= rawurlencode($technician['cases'][0]['public_code']) ?>#asignar-responsable" class="manager-workload-card__secondary"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16m0 0-3-3m3 3-3 3M20 17H4m0 0 3-3m-3 3 3 3"/></svg>Reasignar</a><?php endif; ?></div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
