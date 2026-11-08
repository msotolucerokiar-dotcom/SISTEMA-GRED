<?php
declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$query = $_GET;
unset($query['page']);
$pageUrl = static fn (int $page): string => '/csi/requests?' . http_build_query([...$query, 'page' => $page]);
$statusIds = array_column($filterOptions['statuses'], 'id', 'code');
$counts = array_column($listStatusCounts, 'total', 'code');
$tabQuery = $query;
unset($tabQuery['status']);
$tabUrl = static fn (?int $status): string => '/csi/requests' . ($status === null && $tabQuery === [] ? '' : '?' . http_build_query($status === null ? $tabQuery : [...$tabQuery, 'status' => $status]));
$selectedStatus = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$tabs = [['Todas', $listAllCount, null], ['Pendientes', $counts['pending'] ?? 0, $statusIds['pending'] ?? null], ['En atención', $counts['in_progress'] ?? 0, $statusIds['in_progress'] ?? null], ['Solucionadas', $counts['resolved'] ?? 0, $statusIds['resolved'] ?? null]];
$today = new DateTimeImmutable('now', new DateTimeZone('America/Lima'));
$days = [1 => 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$months = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$dateLabel = $days[(int) $today->format('N')] . ', ' . $today->format('j') . ' de ' . $months[(int) $today->format('n')] . ' de ' . $today->format('Y');
$csrfToken = $session->csrfToken();
?>
<!doctype html>
<html lang="es-PE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Solicitudes | Soporte CSI</title><link rel="stylesheet" href="/assets/app.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/app.css') ?>"></head>
<body class="manager-panel csi-list-page min-h-screen bg-paper text-ink">
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-white focus:p-3">Saltar al contenido</a>
<div class="manager-shell csi-list-shell">
<aside class="manager-sidebar" aria-label="Menú lateral del CSI">
  <div class="manager-sidebar__brand"><span class="manager-sidebar__mark" aria-hidden="true">CSI</span><div><strong>Soporte tecnológico</strong><small>GRED Lambayeque</small></div></div>
  <nav aria-label="Navegación principal" class="manager-sidebar__nav">
    <div class="manager-sidebar__group"><p>Espacio de trabajo</p>
      <a href="/dashboard"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg><span class="manager-sidebar__item-text"><strong>Vista general</strong><small>Resumen operativo</small></span></a>
      <a href="/csi/requests" aria-current="page"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h6"/></svg><span class="manager-sidebar__item-text"><strong>Solicitudes</strong><small>Gestión de atenciones</small></span></a>
      <a href="/dashboard#equipo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-4 2-6 6-6s6 2 6 6M15 15c4 0 6 2 6 5"/></svg><span class="manager-sidebar__item-text"><strong>Equipo y tareas</strong><small>Supervisión del personal</small></span></a>
    </div>
    <?php if ($user->can('report.view') || $user->roleCode === 'csi_manager'): ?><div class="manager-sidebar__group"><p>Administración</p>
      <?php if ($user->can('report.view')): ?><a href="/csi/reports"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 20v-7m5 7V8m5 12v-9m5 9V4"/></svg><span class="manager-sidebar__item-text"><strong>Reportes</strong><small>Resultados del servicio</small></span></a><?php endif; ?>
      <?php if ($user->roleCode === 'csi_manager'): ?><a href="/csi/users"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 20c0-4 3-6 7-6s7 2 7 6"/></svg><span class="manager-sidebar__item-text"><strong>Usuarios y permisos</strong><small>Control de accesos</small></span></a><?php endif; ?>
    </div><?php endif; ?>
  </nav>
</aside>
<div class="csi-list-column">
<header class="manager-topbar"><div class="manager-topbar__inner"><div class="manager-breadcrumb"><a href="/dashboard">GRED LAMBAYEQUE</a><span aria-hidden="true">/</span><span>CSI</span><span aria-hidden="true">/</span><strong aria-current="page">Solicitudes</strong></div><div class="manager-header-actions"><time class="manager-header-date" datetime="<?= $e($today->format('Y-m-d')) ?>"><?= $e($dateLabel) ?></time><?php require __DIR__ . '/partials/notifications-bell.php'; ?><details class="manager-account"><summary aria-label="Opciones de cuenta" title="Opciones de cuenta"><span class="manager-account__avatar" aria-hidden="true"><?= $user->roleCode === 'csi_manager' ? 'RC' : 'TC' ?></span><span class="manager-account__identity"><strong><?= $user->roleCode === 'csi_manager' ? 'Responsable del CSI' : 'Técnico CSI' ?></strong><small><?= $user->roleCode === 'csi_manager' ? 'Administración del servicio' : 'Atención de solicitudes' ?></small></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></summary><div class="manager-account__menu"><a href="/profile">Perfil</a><form action="/logout" method="post"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><button type="submit">Cerrar sesión</button></form></div></details></div></div></header>
<main id="contenido" class="csi-list-content">
  <div class="csi-list-heading"><div><h1>Solicitudes</h1><p>Gestiona, clasifica, asigna y da seguimiento a las solicitudes de soporte.</p></div><?php if ($user->can('request.create')): ?><a href="/requests/new" class="csi-list-create"><span aria-hidden="true">＋</span>Nueva solicitud</a><?php endif; ?></div>
  <?php require __DIR__ . '/partials/csi-list-filters.php'; ?>
  <?php require __DIR__ . '/partials/csi-list-card.php'; ?>
</main></div></div>
<script src="/assets/manager-header-menu.js?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/manager-header-menu.js') ?>" defer></script>
</body></html>
