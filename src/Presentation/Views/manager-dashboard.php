<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$csrfToken = $session->csrfToken();
$statusIds = array_column($managerStatuses, 'id', 'code');
$weekdayNames = [1 => 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$monthNames = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$managerDateLabel = $weekdayNames[(int) $managerToday->format('N')] . ', ' . $managerToday->format('j') . ' de ' . $monthNames[(int) $managerToday->format('n')] . ' de ' . $managerToday->format('Y');
$attentionDate = static function (string $value): string {
    $zone = new DateTimeZone('America/Lima');
    $date = (new DateTimeImmutable($value))->setTimezone($zone);
    $today = new DateTimeImmutable('today', $zone);
    $prefix = $date->format('Y-m-d') === $today->format('Y-m-d') ? 'Hoy' : ($date->format('Y-m-d') === $today->modify('-1 day')->format('Y-m-d') ? 'Ayer' : $date->format('d/m/Y'));
    return $prefix . ', ' . $date->format('H:i');
};
$summaryCards = [
    ['Solicitudes abiertas', $managerOpen, '/csi/requests?' . http_build_query([...$managerDateRange, 'open' => '1']), 'Casos sin solucionar'],
    ['Urgentes abiertas', $managerUrgent, '/csi/requests?' . http_build_query([...$managerDateRange, 'priority' => (int) $urgentPriorityId, 'open' => '1']), 'Requieren atención prioritaria'],
    ['En atención', $managerCounts['in_progress'] ?? 0, '/csi/requests?' . http_build_query([...$managerDateRange, 'status' => $statusIds['in_progress'] ?? 0]), 'Casos en proceso'],
    ['Solucionadas', $managerCounts['resolved'] ?? 0, '/csi/requests?' . http_build_query([...$managerDateRange, 'status' => $statusIds['resolved'] ?? 0]), 'Resueltas en el período'],
    ['Tareas por validar', $managerPendingValidation, '/csi/requests?' . http_build_query([...$managerDateRange, 'validation' => 'pending']), 'Trabajos de practicantes'],
];
?>
<!doctype html>
<html lang="es-PE">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Panel del Responsable CSI | GRED Lambayeque</title><link rel="stylesheet" href="/assets/app.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/app.css') ?>"></head>
<body class="manager-panel min-h-screen bg-paper text-ink">
<a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">Ir al contenido</a>
<header class="manager-topbar">
  <div class="manager-topbar__inner">
    <div class="manager-breadcrumb"><a href="/dashboard">GRED LAMBAYEQUE</a><span aria-hidden="true">/</span><span>Centro de Sistemas de Información</span><span aria-hidden="true">/</span><strong aria-current="page">Inicio</strong></div>
    <div class="manager-header-actions">
      <time class="manager-header-date" datetime="<?= $e($managerToday->format('Y-m-d')) ?>"><?= $e($managerDateLabel) ?></time>
      <?php require __DIR__ . '/partials/notifications-bell.php'; ?>
      <details class="manager-account"><summary aria-label="Opciones de cuenta" title="Opciones de cuenta"><span class="manager-account__avatar" aria-hidden="true">RC</span><span class="manager-account__identity"><strong>Responsable del CSI</strong><small>Administración del servicio</small></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary><div class="manager-account__menu"><a href="/profile">Perfil</a><form action="/logout" method="post"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><button type="submit">Cerrar sesión</button></form></div></details>
    </div>
  </div>
</header>
<div class="manager-shell">
  <aside class="manager-sidebar" aria-label="Menú lateral del Responsable CSI">
    <div class="manager-sidebar__brand"><span class="manager-sidebar__mark" aria-hidden="true">CSI</span><div><strong>Soporte tecnológico</strong><small>GRED Lambayeque</small></div></div>
    <nav aria-label="Navegación principal" class="manager-sidebar__nav">
      <div class="manager-sidebar__group"><p>Espacio de trabajo</p>
        <a href="/dashboard" aria-current="page"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span class="manager-sidebar__item-text"><strong>Vista general</strong><small>Resumen operativo</small></span></a>
        <a href="/csi/requests"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h6"/></svg><span class="manager-sidebar__item-text"><strong>Solicitudes</strong><small>Gestión de atenciones</small></span></a>
        <a href="#equipo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-4 2-6 6-6s6 2 6 6M15 15c4 0 6 2 6 5"/></svg><span class="manager-sidebar__item-text"><strong>Equipo y tareas</strong><small>Supervisión del personal</small></span></a>
      </div>
      <div class="manager-sidebar__group"><p>Administración</p>
        <a href="/csi/reports"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 20v-7m5 7V8m5 12v-9m5 9V4"/></svg><span class="manager-sidebar__item-text"><strong>Reportes</strong><small>Resultados del servicio</small></span></a>
        <a href="/csi/users"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20c0-4 3-6 7-6s7 2 7 6"/></svg><span class="manager-sidebar__item-text"><strong>Usuarios y permisos</strong><small>Control de accesos</small></span></a>
      </div>
    </nav>
  </aside>
<main id="contenido" class="manager-content mx-auto max-w-[90rem] px-5 py-7 sm:py-9">
  <div class="manager-page-heading">
    <div><p class="manager-page-heading__eyebrow">Centro de Sistemas de Información</p><h1>Panel del responsable</h1><p class="manager-page-heading__description">Supervisa las solicitudes de soporte, las asignaciones y el trabajo del equipo.</p></div>
    <form action="/dashboard" method="get" class="manager-period"><label for="manager-period">Período</label><select id="manager-period" name="period" onchange="this.form.requestSubmit()"><option value="all" <?= $managerPeriod === 'all' ? 'selected' : '' ?>>Todas las solicitudes</option><option value="today" <?= $managerPeriod === 'today' ? 'selected' : '' ?>>Hoy</option><option value="7days" <?= $managerPeriod === '7days' ? 'selected' : '' ?>>Últimos 7 días</option><option value="month" <?= $managerPeriod === 'month' ? 'selected' : '' ?>>Este mes</option></select><button type="submit">Aplicar</button></form>
  </div>
  <p class="sr-only">Solicitudes registradas: <?= $managerFrom === null ? 'Todas las solicitudes' : $e($managerPeriodLabel) . ', del ' . $e($managerFrom->format('d/m/Y')) . ' al ' . $e($managerToday->format('d/m/Y')) ?> · hora de Lambayeque · Total: <?= (int) $managerTotal ?></p>
  <?php if (in_array($_GET['saved'] ?? null, ['assign', 'classify', 'classify-category', 'classify-priority'], true)): ?><p role="status" class="mt-5 rounded-xl border border-green-200 bg-green-50 p-4 font-semibold text-green-900">El cambio se guardó correctamente. La lista de intervención se actualizó.</p><?php endif; ?>

  <section id="manager-overview" aria-labelledby="resumen-titulo" class="mt-6"><h2 id="resumen-titulo" class="manager-overview__heading"><span aria-hidden="true"></span>Resumen operativo del período seleccionado</h2><div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <?php foreach ($summaryCards as $index => [$title, $count, $url, $caption]): ?>
      <a href="<?= $e($url) ?>" class="manager-kpi manager-kpi--<?= (int) $index ?> group rounded-xl border transition duration-200 hover:-translate-y-1 hover:shadow-card focus-visible:ring-2 focus-visible:ring-institutional-700"><span class="manager-kpi__title"><?= $e($title) ?></span><span aria-hidden="true" class="manager-kpi__orb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php if ($index === 0): ?><path d="M5 3h10l4 4v14H5zM14 3v5h5M8 12h8M8 16h8"/><?php elseif ($index === 1): ?><path d="m12 3 9 17H3L12 3Z"/><path d="M12 9v5m0 3h.01"/><?php elseif ($index === 2): ?><circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/><?php elseif ($index === 3): ?><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/><?php else: ?><rect x="6" y="4" width="12" height="16" rx="2"/><path d="M9 3h6v3H9zM9 10h6M9 14h4"/><?php endif; ?></svg></span><strong class="manager-kpi__value"><?= (int) $count ?></strong><span class="manager-kpi__caption"><?= $e($caption) ?></span></a>
    <?php endforeach; ?>
  </div></section>

  <div class="mt-5 space-y-5">
      <section id="intervencion" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="intervencion-titulo">
        <div class="manager-attention-toolbar"><div class="manager-attention-heading"><h2 id="intervencion-titulo">Solicitudes que requieren intervención</h2><p>Casos abiertos sin responsable o con información pendiente de clasificación.</p></div><a href="/csi/requests?attention=needed" class="manager-attention-all">Ver todas las solicitudes <span aria-hidden="true">→</span></a></div><p class="sr-only">Solicitudes que necesitan atención: casos abiertos sin responsable, categoría o prioridad. Se muestran primero los más antiguos.</p>
        <?php if ($managerAttention === []): ?><p class="mt-5 rounded-xl bg-institutional-50 p-5 text-slate-700">No hay solicitudes abiertas pendientes de asignación o clasificación.</p><?php else: ?>
        <div class="manager-attention-scroll"><div class="manager-attention-head"><span>Incidencia / código</span><span>Oficina / área</span><span>Situación</span><span>Prioridad</span><span>Registro</span><span>Acción</span></div><ol id="manager-attention-actions" class="manager-attention-list">
          <?php foreach ($managerAttention as $item): ?>
          <?php
            $rowActions = [];
            if ($user->can('request.assign') && $item['technician_id'] === null) { $rowActions[] = ['Asignar', 'asignar']; }
            if ($user->can('request.classify') && $item['category_id'] === null) { $rowActions[] = ['Clasificar', 'clasificar']; }
            if ($user->can('request.classify') && $item['priority_id'] === null) { $rowActions[] = ['Priorizar', 'priorizar']; }
          ?>
          <li class="manager-attention-item"><div class="manager-attention-row">
            <div class="manager-code-cell"><span class="manager-cell-label">Incidencia / código</span><a href="/csi/requests/<?= rawurlencode($item['public_code']) ?>" class="manager-request-code"><?= $e($item['public_code']) ?></a><h3 class="manager-request-title"><?= $e($item['title']) ?></h3></div>
            <div class="min-w-0"><span class="manager-cell-label">Oficina / área</span><span class="manager-request-area"><?= $e($item['area_name']) ?></span></div>
            <div><span class="manager-cell-label">Situación</span><span class="manager-state <?= $item['technician_id'] === null ? 'manager-state--unassigned' : 'manager-state--missing' ?>"><?= $item['technician_id'] === null ? 'Sin asignar' : ($item['category_id'] === null ? 'Sin categoría' : 'Sin prioridad') ?></span></div>
            <div><span class="manager-cell-label">Prioridad</span><span data-priority="<?= $e($item['priority_name'] ?? '') ?>" class="manager-priority <?= $item['priority_id'] === null ? 'manager-priority--missing' : '' ?>"><?= $e($item['priority_id'] === null ? 'Sin definir' : ($item['priority_name'] ?? 'Definida')) ?></span></div>
            <div><span class="manager-cell-label">Registro</span><time class="manager-request-date" datetime="<?= $e((new DateTimeImmutable($item['created_at']))->format(DATE_ATOM)) ?>"><?= $e($attentionDate($item['created_at'])) ?></time></div>
            <div class="manager-row-actions"><span class="manager-cell-label">Acción</span><?php if ($rowActions !== []): ?><button type="button" class="manager-mini-action" data-modal-open="<?= $e($rowActions[0][1] . '-' . $item['public_code']) ?>"><?= $e($rowActions[0][0]) ?></button><?php if (count($rowActions) > 1): ?><details class="manager-attention-more" data-workload-menu><summary aria-label="Más acciones para <?= $e($item['public_code']) ?>" title="Más acciones">···</summary><div class="manager-attention-more__menu"><?php foreach (array_slice($rowActions, 1) as [$actionLabel, $actionType]): ?><button type="button" data-modal-open="<?= $e($actionType . '-' . $item['public_code']) ?>"><?= $e($actionLabel) ?></button><?php endforeach; ?></div></details><?php endif; ?><?php else: ?><a class="manager-mini-action" href="/csi/requests/<?= rawurlencode($item['public_code']) ?>">Ver detalle</a><?php endif; ?></div>
          </div>
          <?php require __DIR__ . '/partials/manager-attention-modals.php'; ?></li>
          <?php endforeach; ?>
        </ol></div><?php endif; ?>
      </section>

      <div class="manager-secondary-grid">
        <?php require __DIR__ . '/partials/manager-workload.php'; ?>
        <?php require __DIR__ . '/partials/manager-follow-up.php'; ?>
      </div>
  </div>
</main>
</div>
<script src="/assets/manager-action-dialogs.js?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/manager-action-dialogs.js') ?>" defer></script>
<script src="/assets/manager-header-menu.js?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/manager-header-menu.js') ?>" defer></script>
</body></html>
