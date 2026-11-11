<?php
declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$query = $_GET;
unset($query['page']);
$pageUrl = static fn (int $page): string => '/csi/requests?' . http_build_query([...$query, 'page' => $page]);
$statusIds = array_column($filterOptions['statuses'], 'id', 'code');
$counts = array_column($listStatusCounts, 'total', 'code');
$tabQuery = $query;
unset($tabQuery['status'], $tabQuery['assignment'], $tabQuery['attention'], $tabQuery['open'], $tabQuery['technician']);
$tabUrl = static fn (array $filters): string => '/csi/requests' . ($tabQuery === [] && $filters === [] ? '' : '?' . http_build_query([...$tabQuery, ...$filters]));
$selectedStatus = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$tabs = [
    ['Activas', $listMetrics['active'], ['open' => '1'], ($_GET['open'] ?? '') === '1' && $selectedStatus === '' && ($_GET['assignment'] ?? '') === '' && ($_GET['technician'] ?? '') === ''],
    ['Sin asignar', $listUnassignedCount, ['assignment' => 'unassigned', 'open' => '1'], ($_GET['assignment'] ?? '') === 'unassigned'],
    ['Asignadas a mí', null, ['technician' => $user->id, 'open' => '1'], ($_GET['technician'] ?? '') === (string) $user->id],
    ['En atención', $counts['in_progress'] ?? 0, ['status' => $statusIds['in_progress'] ?? 0], $selectedStatus === (string) ($statusIds['in_progress'] ?? 0)],
    ['Por cerrar', $counts['resolved'] ?? 0, ['status' => $statusIds['resolved'] ?? 0], $selectedStatus === (string) ($statusIds['resolved'] ?? 0)],
    ['Finalizadas', $counts['closed'] ?? 0, ['status' => $statusIds['closed'] ?? 0], $selectedStatus === (string) ($statusIds['closed'] ?? 0)],
];
$csrfToken = $session->csrfToken();
$displayName = \App\Presentation\DisplayName::withoutDemoMarker($user->fullName);
$nameParts = preg_split('/\s+/u', trim($displayName)) ?: [];
$initials = mb_strtoupper(mb_substr($nameParts[0] ?? '', 0, 1) . mb_substr($nameParts[1] ?? '', 0, 1));
?>
<!doctype html>
<html lang="es-PE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Solicitudes | Soporte CSI</title><link rel="stylesheet" href="/assets/app.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/app.css') ?>"></head>
<body class="manager-panel csi-list-page min-h-screen bg-paper text-ink">
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:p-3">Saltar al contenido</a>
<header class="manager-topbar"><div class="manager-topbar__inner"><div class="manager-breadcrumb"><a href="/dashboard">GRED LAMBAYEQUE</a><span aria-hidden="true">/</span><span>Centro de Sistemas de Informaci&oacute;n</span><span aria-hidden="true">/</span><strong aria-current="page">Solicitudes</strong></div><form class="manager-top-search" action="/csi/requests" method="get" role="search"><label class="sr-only" for="csi-global-search">Buscar solicitudes</label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input id="csi-global-search" type="search" name="search" maxlength="100" placeholder="Buscar solicitudes, oficinas, trabajadores o códigos..."><button class="sr-only focus:not-sr-only" type="submit">Buscar</button></form><div class="manager-header-actions"><?php require __DIR__ . '/partials/notifications-bell.php'; ?><details class="manager-account"><summary aria-label="Opciones de cuenta" title="Opciones de cuenta"><span class="manager-account__avatar" aria-hidden="true"><?= $e($initials) ?></span><span class="manager-account__identity"><strong><?= $e($displayName) ?></strong><small><?= $user->roleCode === 'csi_manager' ? 'Administración del servicio' : 'Atención de solicitudes' ?></small></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary><div class="manager-account__menu"><a href="/profile">Perfil</a><form action="/logout" method="post"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><button type="submit">Cerrar sesión</button></form></div></details></div></div></header>
<div class="manager-shell csi-list-shell">
<aside class="manager-sidebar" aria-label="Menú lateral del CSI">
  <div class="manager-sidebar__brand"><span class="manager-sidebar__mark" aria-hidden="true">CSI</span><div><strong>Soporte tecnológico</strong><small>GRED Lambayeque</small></div></div>
  <?php require __DIR__ . '/partials/manager-sidebar-nav.php'; ?>
</aside>
<div class="csi-list-column">

<main id="contenido" class="csi-list-content">
  <div class="csi-list-heading"><div class="csi-list-heading__title"><span class="csi-list-heading__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h6"/></svg></span><div><h1>Solicitudes</h1><p>Consulta y gestiona las solicitudes de soporte tecnol&oacute;gico de las oficinas.</p></div></div><div class="csi-list-heading__actions"><?php if ($user->can('request.create')): ?><a class="csi-list-create" href="/requests/new"><span aria-hidden="true">+</span> Registrar solicitud</a><?php endif; ?></div></div>
  <?php require __DIR__ . '/partials/csi-list-summary.php'; ?>
  <div class="csi-list-utilities"><?php if ($user->can('report.view')): ?><details class="csi-list-export"><summary><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v12m-4-4 4 4-4M4 17v4h16v-4"/></svg>Exportar <span aria-hidden="true">&#9662;</span></summary><div><a href="/csi/reports.csv">Descargar CSV</a><a href="/csi/reports.pdf">Descargar PDF</a></div></details><?php endif; ?></div>
  <div class="csi-list-workspace"><div class="csi-list-workspace__main">
  <nav class="csi-list-tabs csi-list-tabs--primary" aria-label="Filtrar solicitudes"><?php foreach ($tabs as [$label, $count, $filters, $active]): ?><a href="<?= $e($tabUrl($filters)) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= $e($label) ?><?php if ($count !== null): ?> <span><?= (int) $count ?></span><?php endif; ?></a><?php endforeach; ?></nav>
  <?php require __DIR__ . '/partials/csi-list-filters.php'; ?>
  <div class="csi-list-alerts"><strong><span aria-hidden="true">⚠</span> Gestiones pendientes</strong><a class="csi-list-alerts__urgent" href="/csi/requests?priority=<?= (int) (array_column($filterOptions['priorities'], 'id', 'code')['urgent'] ?? 0) ?>&amp;assignment=unassigned&amp;open=1">● <?= (int) $listAlertCounts['urgent_unassigned'] ?> urgentes sin asignar</a><a class="csi-list-alerts__stale" href="/csi/requests?alert=stale" title="Solicitudes activas asignadas sin movimientos durante 48 horas">● <?= (int) $listAlertCounts['stale'] ?> con demora</a><a class="csi-list-alerts__priority" href="/csi/requests?alert=missing_priority">● <?= (int) $listAlertCounts['missing_priority'] ?> sin prioridad</a><a class="csi-list-alerts__category" href="/csi/requests?alert=missing_category">● <?= (int) $listAlertCounts['missing_category'] ?> por clasificar</a><small>Selecciona un aviso para filtrar ›</small></div>
  <?php require __DIR__ . '/partials/csi-list-card.php'; ?>
  </div></div>
</main></div></div>
<script src="/assets/manager-header-menu.js?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/manager-header-menu.js') ?>" defer></script>
</body></html>
