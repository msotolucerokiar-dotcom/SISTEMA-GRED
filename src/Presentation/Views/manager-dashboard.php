<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$csrfToken = $session->csrfToken();
$displayName = \App\Presentation\DisplayName::withoutDemoMarker($user->fullName);
$nameParts = preg_split('/\s+/u', trim($displayName)) ?: [];
$initials = mb_strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[1] ?? '', 0, 1));
$statusIds = array_column($managerStatuses, 'id', 'code');
$attentionDate = static function (string $value): string {
    $zone = new DateTimeZone('America/Lima');
    $date = (new DateTimeImmutable($value))->setTimezone($zone);
    $today = new DateTimeImmutable('today', $zone);
    $prefix = $date->format('Y-m-d') === $today->format('Y-m-d') ? 'Hoy' : ($date->format('Y-m-d') === $today->modify('-1 day')->format('Y-m-d') ? 'Ayer' : $date->format('d/m/Y'));
    return $prefix . ', ' . $date->format('H:i');
};
$summaryCards = [
    ['Nuevas por revisar', $managerNewToReview, '/csi/requests?' . http_build_query([...$managerDateRange, 'status' => $pendingStatusId, 'assignment' => 'unassigned']), 'Solicitudes pendientes de primera revisión'],
    ['Decisiones pendientes', $managerDecisionsPending, '/csi/requests?' . http_build_query([...$managerDateRange, 'attention' => 'needed']), 'Clasificación, prioridad o asignación'],
    ['En atención', $managerCounts['in_progress'] ?? 0, '/csi/requests?' . http_build_query([...$managerDateRange, 'status' => $statusIds['in_progress'] ?? 0]), 'Atenciones técnicas en curso'],
    ['Por validar', $managerPendingValidation, '/csi/requests?' . http_build_query([...$managerDateRange, 'validation' => 'pending']), 'Trabajos pendientes de revisión'],
];
?>
<!doctype html>
<html lang="es-PE">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Panel del Responsable CSI | GRED Lambayeque</title><link rel="stylesheet" href="/assets/app.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/app.css') ?>"></head>
<body class="manager-panel manager-overview-page min-h-screen bg-paper text-ink">
<a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">Ir al contenido</a>
<header class="manager-topbar">
  <div class="manager-topbar__inner">
    <div class="manager-breadcrumb"><a href="/dashboard">Panel del responsable</a><span aria-hidden="true">&rsaquo;</span><strong aria-current="page"><?= ($_GET['section'] ?? '') === 'team' ? 'Equipo y tareas' : 'Vista general' ?></strong></div>
    <form class="manager-top-search" action="/csi/requests" method="get" role="search"><label class="sr-only" for="manager-global-search">Buscar solicitudes</label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input id="manager-global-search" type="search" name="search" maxlength="100" placeholder="Buscar solicitudes, oficinas, trabajadores o códigos..."><button class="sr-only focus:not-sr-only" type="submit">Buscar</button></form>
    <div class="manager-header-actions">
      <?php require __DIR__ . '/partials/notifications-bell.php'; ?>
      <details class="manager-account"><summary aria-label="Opciones de cuenta" title="Opciones de cuenta"><span class="manager-account__avatar" aria-hidden="true"><?= $e($initials) ?></span><span class="manager-account__identity"><strong><?= $e($displayName) ?></strong><small>Responsable del CSI</small></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary><div class="manager-account__menu"><a href="/profile">Perfil</a><form action="/logout" method="post"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><button type="submit">Cerrar sesi&oacute;n</button></form></div></details>
    </div>
  </div>
</header>
<div class="manager-shell">
  <aside class="manager-sidebar" aria-label="Menú lateral del Responsable CSI">
    <div class="manager-institution"><div><span aria-hidden="true">GRL</span><strong>GOBIERNO REGIONAL<br>DE LAMBAYEQUE</strong></div><div><span aria-hidden="true">G</span><p><strong>GRED</strong><small>Gerencia Regional de Educaci&oacute;n</small></p></div><div><span aria-hidden="true">CSI</span><p><strong>CSI</strong><small>Centro de Sistemas de Informaci&oacute;n</small></p></div></div>
    <?php require __DIR__ . '/partials/manager-sidebar-nav.php'; ?>
    <div class="manager-institution-footer"><strong>Sistema de soporte tecnol&oacute;gico</strong><small>Uso institucional &middot; CSI<br>GRED Lambayeque</small></div>
  </aside>
<main id="contenido" class="manager-content mx-auto max-w-[90rem] px-5 py-7 sm:py-9">
  <?php if (($_GET['section'] ?? '') === 'team'): ?>
    <?php require __DIR__ . '/partials/manager-team.php'; ?>
  <?php else: ?>
  <div class="manager-page-heading">
    <div class="manager-page-heading__main"><span class="manager-page-heading__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span><div><h1>Vista general</h1><p class="manager-page-heading__description">Supervisa las atenciones y gestiona las decisiones pendientes del CSI.</p></div></div>
    <?php if ($user->can('request.create')): ?><div class="manager-page-heading__actions"><a class="manager-register" href="/requests/new"><span aria-hidden="true">+</span> Registrar solicitud</a></div><?php endif; ?>
  </div>
  <p class="sr-only">Solicitudes registradas: <?= $managerFrom === null ? 'Todas las solicitudes' : $e($managerPeriodLabel) . ', del ' . $e($managerFrom->format('d/m/Y')) . ' al ' . $e($managerToday->format('d/m/Y')) ?> · hora de Lambayeque · Total: <?= (int) $managerTotal ?></p>
  <?php if (in_array($_GET['saved'] ?? null, ['assign', 'classify', 'classify-category', 'classify-priority'], true)): ?><p role="status" class="mt-5 rounded-xl border border-green-200 bg-green-50 p-4 font-semibold text-green-900">El cambio se guardó correctamente. La lista de intervención se actualizó.</p><?php endif; ?>

  <section id="manager-overview" aria-label="Resumen operativo" class="manager-overview"><div class="manager-overview__cards">
    <?php foreach ($summaryCards as $index => [$title, $count, $url, $caption]): ?>
      <a href="<?= $e($url) ?>" class="manager-kpi manager-kpi--<?= (int) $index ?>"><span aria-hidden="true" class="manager-kpi__orb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php if ($index === 0): ?><path d="M4 6h16v12H4zM4 12h16M8 15h8"/><?php elseif ($index === 1): ?><rect x="6" y="4" width="12" height="16" rx="2"/><path d="M9 3h6v3H9zM9 10h6M9 14h4"/><?php elseif ($index === 2): ?><path d="M6 14v-3a6 6 0 0 1 12 0v3M4 14h4v5H4zM16 14h4v5h-4zM18 19c0 2-2 3-5 3"/><?php else: ?><rect x="6" y="4" width="12" height="16" rx="2"/><path d="M9 3h6v3H9zM9 12l2 2 4-4"/><?php endif; ?></svg></span><span class="manager-kpi__body"><strong class="manager-kpi__value"><?= (int) $count ?></strong><span class="manager-kpi__title"><?= $e($title) ?></span><span class="manager-kpi__caption"><?= $e($caption) ?></span></span><span class="manager-kpi__arrow" aria-hidden="true">&#8250;</span></a>
    <?php endforeach; ?>
  </div></section>
  <section class="manager-alerts" aria-label="Alertas prioritarias"><h2><span aria-hidden="true">&#9888;</span> Alertas prioritarias</h2><a href="/csi/requests?<?= $e(http_build_query([...$managerDateRange, 'priority' => (int) $urgentPriorityId, 'assignment' => 'unassigned', 'open' => '1'])) ?>" class="manager-alerts__item manager-alerts__item--urgent <?= $managerPriorityUnassigned === 0 ? 'manager-alerts__item--clear' : '' ?>"><strong><?= (int) $managerPriorityUnassigned ?> solicitudes urgentes</strong> sin asignar <span>Ver solicitudes &#8250;</span></a><a href="/csi/requests?<?= $e(http_build_query([...$managerDateRange, 'status' => (int) $pendingStatusId, 'assignment' => 'assigned'])) ?>" class="manager-alerts__item manager-alerts__item--pending"><strong><?= (int) $managerPendingAssigned ?> solicitudes asignadas</strong> a&uacute;n no inician atenci&oacute;n <span>Revisar &#8250;</span></a></section>
  <div class="mt-5 space-y-5">
      <section id="intervencion" class="manager-intervention rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="intervencion-titulo">
        <div class="manager-attention-toolbar"><div class="manager-attention-heading"><h2 id="intervencion-titulo">Solicitudes que requieren mi intervenci&oacute;n</h2><p>Incidencias que necesitan una decisi&oacute;n para continuar su atenci&oacute;n.</p></div><span class="manager-attention-count">Mostrando <?= count($managerAttention) ?> de <?= (int) $managerAttentionTotal ?> decisiones pendientes</span></div><p class="sr-only">Solicitudes que necesitan atenci&oacute;n: casos abiertos sin responsable, categor&iacute;a o prioridad. Se muestran primero los m&aacute;s antiguos.</p>
        <?php if ($managerAttention === []): ?><div class="manager-intervention__empty"><span aria-hidden="true">&#10003;</span><p><strong>No hay solicitudes pendientes de intervenci&oacute;n</strong><small>No hay solicitudes por asignar, clasificar o priorizar en este momento.</small></p></div><?php else: ?>
        <div class="manager-intervention__scroll"><div class="manager-intervention__head"><span>Solicitud / oficina</span><span>Problema</span><span>Prioridad</span><span>Pendiente de</span><span>Espera de gesti&oacute;n</span><span>Acci&oacute;n</span></div><ol id="manager-attention-actions" class="manager-intervention__list">
          <?php foreach ($managerAttention as $item): ?>
          <?php
            $rowActions = [];
            if ($user->can('request.assign') && $item['technician_id'] === null) { $rowActions[] = ['Asignar', 'asignar']; }
            if ($user->can('request.classify') && $item['category_id'] === null) { $rowActions[] = ['Clasificar', 'clasificar']; }
            if ($user->can('request.classify') && $item['priority_id'] === null) { $rowActions[] = ['Priorizar', 'priorizar']; }
            $pendingReason = $item['technician_id'] === null ? 'Asignación' : ($item['category_id'] === null ? 'Clasificación' : 'Prioridad');
            $waitingMinutes = max(0, (int) floor(((new DateTimeImmutable('now'))->getTimestamp() - (new DateTimeImmutable($item['created_at']))->getTimestamp()) / 60));
            $waitingLabel = $waitingMinutes >= 1440 ? intdiv($waitingMinutes, 1440) . ' d ' . intdiv($waitingMinutes % 1440, 60) . ' h' : intdiv($waitingMinutes, 60) . ' h ' . ($waitingMinutes % 60) . ' min';
          ?>
          <li class="manager-intervention__item"><div class="manager-intervention__row">
            <div><span class="manager-cell-label">Solicitud / oficina</span><a href="/csi/requests/<?= rawurlencode($item['public_code']) ?>" class="manager-request-code"><?= $e($item['public_code']) ?></a><small class="manager-intervention__area"><?= $e($item['area_name']) ?></small></div>
            <div><span class="manager-cell-label">Problema</span><h3 class="manager-request-title"><?= $e($item['title']) ?></h3></div>
            <div><span class="manager-cell-label">Prioridad</span><span data-priority="<?= $e($item['priority_name'] ?? '') ?>" class="manager-priority <?= $item['priority_id'] === null ? 'manager-priority--missing' : '' ?>"><?= $e($item['priority_id'] === null ? 'Sin definir' : ($item['priority_name'] ?? 'Definida')) ?></span></div>
            <div><span class="manager-cell-label">Pendiente de</span><?= $e($pendingReason) ?></div>
            <div><span class="manager-cell-label">Espera de gesti&oacute;n</span><time datetime="<?= $e((new DateTimeImmutable($item['created_at']))->format(DATE_ATOM)) ?>" title="Registrada el <?= $e($attentionDate($item['created_at'])) ?>"><?= $e($waitingLabel) ?></time></div>
            <div class="manager-row-actions"><span class="manager-cell-label">Acci&oacute;n</span><?php if ($rowActions !== []): ?><button type="button" class="manager-mini-action" data-modal-open="<?= $e($rowActions[0][1] . '-' . $item['public_code']) ?>"><?= $e($rowActions[0][0]) ?></button><?php if (count($rowActions) > 1): ?><details class="manager-attention-more" data-workload-menu><summary aria-label="M&aacute;s acciones para <?= $e($item['public_code']) ?>" title="M&aacute;s acciones">&#8230;</summary><div class="manager-attention-more__menu"><?php foreach (array_slice($rowActions, 1) as [$actionLabel, $actionType]): ?><button type="button" data-modal-open="<?= $e($actionType . '-' . $item['public_code']) ?>"><?= $e($actionLabel) ?></button><?php endforeach; ?></div></details><?php endif; ?><?php else: ?><a class="manager-mini-action" href="/csi/requests/<?= rawurlencode($item['public_code']) ?>">Revisar</a><?php endif; ?></div>
          </div>
          <?php require __DIR__ . '/partials/manager-attention-modals.php'; ?></li>
          <?php endforeach; ?>
        </ol></div><?php endif; ?>
        <div class="manager-intervention__footer"><span>Ordenadas por antig&uuml;edad y necesidad de intervenci&oacute;n</span><a href="/csi/requests?attention=needed">Ver todas las solicitudes que requieren intervenci&oacute;n &#8250;</a></div>
      </section>

      <div class="manager-secondary-grid">
        <?php require __DIR__ . '/partials/manager-workload.php'; ?>
        <?php require __DIR__ . '/partials/manager-follow-up.php'; ?>
      </div>
  </div>
  <?php endif; ?>
</main>
</div>
<script src="/assets/manager-action-dialogs.js?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/manager-action-dialogs.js') ?>" defer></script>
<script src="/assets/manager-header-menu.js?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/manager-header-menu.js') ?>" defer></script>
</body></html>
