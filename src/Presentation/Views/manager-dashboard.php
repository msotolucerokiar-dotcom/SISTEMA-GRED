<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$csrfToken = $session->csrfToken();
$statusIds = array_column($managerStatuses, 'id', 'code');
$localDate = static fn (string $value): string => (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('America/Lima'))->format('d/m/Y H:i');
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
<header class="manager-topbar bg-institutional-800 text-white">
  <div class="manager-topbar__inner">
    <div class="manager-brand"><a href="/dashboard" class="manager-brand__name">CSI - GRED</a><span class="manager-brand__section">Mi panel</span></div>
    <nav class="manager-topnav flex max-w-full flex-wrap items-center gap-1 text-sm font-semibold" aria-label="Navegación principal">
      <a href="/dashboard" aria-current="page" class="min-h-11 rounded-lg border-b-2 border-amber-400 bg-white/15 px-3 py-3"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 11 9-7 9 7v9H3zM9 20v-7h6v7"/></svg> Inicio</a>
      <a href="/csi/requests" class="min-h-11 rounded-lg px-3 py-3 hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-white"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h6"/></svg> Solicitudes</a>
      <a href="#intervencion" class="min-h-11 rounded-lg px-3 py-3 hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-white"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-4 2-6 6-6s6 2 6 6M15 15c4 0 6 2 6 5"/></svg> Supervisión</a>
      <a href="/csi/reports" class="min-h-11 rounded-lg px-3 py-3 hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-white"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 20v-7m5 7V8m5 12v-9m5 9V4"/></svg> Reportes</a>
      <a href="/csi/users" class="min-h-11 rounded-lg px-3 py-3 hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-white"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20c0-4 3-6 7-6s7 2 7 6"/></svg> Usuarios</a>
    </nav>
    <div class="manager-header-actions">
      <?php require __DIR__ . '/partials/notifications-bell.php'; ?>
      <details class="manager-account"><summary aria-label="Opciones de cuenta" title="Opciones de cuenta"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20c0-4 3-6 7-6s7 2 7 6"/></svg></summary><div class="manager-account__menu"><a href="/profile">Perfil</a><form action="/logout" method="post"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><button type="submit">Cerrar sesión</button></form></div></details>
    </div>
  </div>
</header>
<main id="contenido" class="mx-auto max-w-[90rem] px-5 py-7 sm:py-9">
  <div class="flex flex-wrap items-end justify-between gap-5">
    <div><p class="text-sm font-bold text-institutional-700">Panel · Responsable del CSI</p><h1 class="mt-1 text-3xl font-extrabold sm:text-4xl">Hola, <?= $e(\App\Presentation\DisplayName::withoutDemoMarker($user->fullName)) ?></h1><p class="mt-2 text-slate-600">Supervisa la atención y administra el servicio de soporte del SISTEMA-GRED.</p></div>
    <form action="/dashboard" method="get" class="manager-period"><label for="manager-period">Período:</label><select id="manager-period" name="period" onchange="this.form.requestSubmit()"><option value="all" <?= $managerPeriod === 'all' ? 'selected' : '' ?>>Todas las solicitudes</option><option value="today" <?= $managerPeriod === 'today' ? 'selected' : '' ?>>Hoy</option><option value="7days" <?= $managerPeriod === '7days' ? 'selected' : '' ?>>Últimos siete días</option><option value="month" <?= $managerPeriod === 'month' ? 'selected' : '' ?>>Este mes</option></select><button type="submit">Aplicar</button></form>
  </div>
  <p class="mt-4 text-sm text-slate-600">Solicitudes registradas: <strong><?= $managerFrom === null ? 'Todas las solicitudes' : $e($managerPeriodLabel) . ', del ' . $e($managerFrom->format('d/m/Y')) . ' al ' . $e($managerToday->format('d/m/Y')) ?></strong> · hora de Lambayeque · Total: <strong><?= (int) $managerTotal ?></strong></p>
  <?php if (in_array($_GET['saved'] ?? null, ['assign', 'classify', 'classify-category', 'classify-priority'], true)): ?><p role="status" class="mt-5 rounded-xl border border-green-200 bg-green-50 p-4 font-semibold text-green-900">El cambio se guardó correctamente. La lista de intervención se actualizó.</p><?php endif; ?>

  <section id="manager-overview" aria-labelledby="resumen-titulo" class="mt-6"><h2 id="resumen-titulo" class="manager-overview__heading"><span aria-hidden="true"></span>Resumen operativo del período seleccionado</h2><div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <?php foreach ($summaryCards as $index => [$title, $count, $url, $caption]): ?>
      <a href="<?= $e($url) ?>" class="manager-kpi manager-kpi--<?= (int) $index ?> group rounded-xl border transition duration-200 hover:-translate-y-1 hover:shadow-card focus-visible:ring-2 focus-visible:ring-institutional-700"><span class="manager-kpi__title"><?= $e($title) ?></span><span aria-hidden="true" class="manager-kpi__orb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php if ($index === 0): ?><path d="M5 3h10l4 4v14H5zM14 3v5h5M8 12h8M8 16h8"/><?php elseif ($index === 1): ?><path d="m12 3 9 17H3L12 3Z"/><path d="M12 9v5m0 3h.01"/><?php elseif ($index === 2): ?><circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/><?php elseif ($index === 3): ?><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/><?php else: ?><rect x="6" y="4" width="12" height="16" rx="2"/><path d="M9 3h6v3H9zM9 10h6M9 14h4"/><?php endif; ?></svg></span><strong class="manager-kpi__value"><?= (int) $count ?></strong><span class="manager-kpi__caption"><?= $e($caption) ?></span></a>
    <?php endforeach; ?>
  </div></section>

  <div class="mt-5 space-y-5">
      <section id="intervencion" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="intervencion-titulo">
        <div class="manager-attention-toolbar"><div class="manager-attention-heading"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M8 5h13M8 12h13M8 19h13"/><circle cx="3" cy="5" r="1"/><circle cx="3" cy="12" r="1"/><circle cx="3" cy="19" r="1"/></svg><h2 id="intervencion-titulo" class="text-xl font-extrabold">Solicitudes que requieren intervención</h2></div><a href="/csi/requests?attention=needed" class="manager-attention-all">Ver todas las solicitudes <span aria-hidden="true">›</span></a></div><p class="sr-only">Solicitudes que necesitan atención: casos abiertos sin responsable, categoría o prioridad. Se muestran primero los más antiguos.</p>
        <?php if ($managerAttention === []): ?><p class="mt-5 rounded-xl bg-institutional-50 p-5 text-slate-700">No hay solicitudes abiertas pendientes de asignación o clasificación.</p><?php else: ?>
        <div class="manager-attention-scroll"><div class="manager-attention-head"><span>Código</span><span>Incidencia / solicitud</span><span>Oficina / área</span><span>Prioridad</span><span>Estado</span><span>Acciones</span></div><ol id="manager-attention-actions" class="manager-attention-list">
          <?php foreach ($managerAttention as $item): ?>
          <li class="manager-attention-item"><div class="manager-attention-row">
            <div class="manager-code-cell"><span class="manager-cell-label">Código</span><div class="manager-code-content"><a href="/csi/requests/<?= rawurlencode($item['public_code']) ?>" class="manager-request-code font-extrabold text-institutional-700 underline"><?= $e($item['public_code']) ?></a></div></div>
            <div class="min-w-0"><span class="manager-cell-label">Solicitud</span><h3 class="manager-request-title"><?= $e($item['title']) ?></h3><span class="manager-request-date text-xs text-slate-500"><?= $e($localDate($item['created_at'])) ?></span><?php if ($item['category_id'] === null): ?><span class="manager-missing-category">Sin categoría</span><?php endif; ?></div>
            <div class="min-w-0"><span class="manager-cell-label">Oficina / área</span><span class="manager-request-area"><?= $e($item['area_name']) ?></span></div>
            <div><span class="manager-cell-label">Prioridad</span><span data-priority="<?= $e($item['priority_name'] ?? '') ?>" class="manager-priority <?= $item['priority_id'] === null ? 'manager-priority--missing' : '' ?>"><?= $e($item['priority_id'] === null ? 'Sin prioridad' : ($item['priority_name'] ?? 'Definida')) ?></span></div>
            <div><span class="manager-cell-label">Estado</span><span class="manager-state <?= $item['technician_id'] === null ? 'manager-state--unassigned' : '' ?>"><?= $e($item['status_name']) ?> · <?= $item['technician_id'] === null ? 'Sin asignar' : 'Asignada' ?></span></div>
            <div class="manager-row-actions"><span class="manager-cell-label">Acciones</span><a href="/csi/requests/<?= rawurlencode($item['public_code']) ?>" class="manager-detail-link" aria-label="Ver detalle de <?= $e($item['public_code']) ?>" title="Ver detalle"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg></a><?php if ($user->can('request.assign') && $item['technician_id'] === null): ?><button type="button" class="manager-mini-action" data-modal-open="asignar-<?= $e($item['public_code']) ?>">Asignar</button><?php endif; ?><?php if ($user->can('request.classify') && $item['category_id'] === null): ?><button type="button" class="manager-mini-action" data-modal-open="clasificar-<?= $e($item['public_code']) ?>">Clasificar</button><?php endif; ?><?php if ($user->can('request.classify') && $item['priority_id'] === null): ?><button type="button" class="manager-mini-action" data-modal-open="priorizar-<?= $e($item['public_code']) ?>">Priorizar</button><?php endif; ?></div>
          </div>
          <?php require __DIR__ . '/partials/manager-attention-modals.php'; ?></li>
          <?php endforeach; ?>
        </ol></div><?php endif; ?>
      </section>

      <?php require __DIR__ . '/partials/manager-workload.php'; ?>
      <?php require __DIR__ . '/partials/manager-follow-up.php'; ?>
  </div>
</main>
<script src="/assets/manager-action-dialogs.js?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/manager-action-dialogs.js') ?>" defer></script>
</body></html>
