<?php
declare(strict_types=1);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$activeSection = match (true) {
    $currentPath === '/dashboard' && ($_GET['section'] ?? '') === 'team' => 'team',
    $currentPath === '/dashboard' => 'overview',
    $currentPath === '/csi/requests', $currentPath === '/technician/requests' => 'requests',
    $currentPath === '/csi/reports' => 'reports',
    $currentPath === '/csi/users' => 'administration',
    default => '',
};
$isManager = $user->roleCode === 'csi_manager';
?>
<nav aria-label="Navegación principal" class="manager-sidebar__nav">
  <div class="manager-sidebar__group">
    <p>Navegación</p>
    <a href="/dashboard" <?= $activeSection === 'overview' ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg><span class="manager-sidebar__item-text"><strong>Vista general</strong></span></a>
    <a href="<?= $isManager ? '/csi/requests' : '/technician/requests' ?>" <?= $activeSection === 'requests' ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h6"/></svg><span class="manager-sidebar__item-text"><strong>Solicitudes</strong></span></a>
    <?php if ($isManager): ?><a href="/dashboard?section=team" <?= $activeSection === 'team' ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-4 2-6 6-6s6 2 6 6M15 15c4 0 6 2 6 5"/></svg><span class="manager-sidebar__item-text"><strong>Equipo y tareas</strong></span></a><?php endif; ?>
    <?php if ($user->can('report.view')): ?><a href="/csi/reports" <?= $activeSection === 'reports' ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 20v-7m5 7V8m5 12v-9m5 9V4"/></svg><span class="manager-sidebar__item-text"><strong>Reportes</strong></span></a><?php endif; ?>
    <?php if ($isManager): ?><a href="/csi/users" <?= $activeSection === 'administration' ? 'aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.82 1.82-.06-.06A1.7 1.7 0 0 0 16 18.4a1.7 1.7 0 0 0-1 1.57V20h-2.58v-.08a1.7 1.7 0 0 0-1-1.57 1.7 1.7 0 0 0-1.88.34l-.06.06-1.82-1.82.06-.06A1.7 1.7 0 0 0 8.06 15a1.7 1.7 0 0 0-1.57-1H6.4v-2.58h.09a1.7 1.7 0 0 0 1.57-1 1.7 1.7 0 0 0-.34-1.88l-.06-.06 1.82-1.82.06.06A1.7 1.7 0 0 0 11.42 8a1.7 1.7 0 0 0 1-1.57V6.4H15v.09A1.7 1.7 0 0 0 16 8a1.7 1.7 0 0 0 1.88-.34l.06-.06 1.82 1.82-.06.06A1.7 1.7 0 0 0 19.4 11a1.7 1.7 0 0 0 1.57 1h.03v2.58h-.03A1.7 1.7 0 0 0 19.4 15z" transform="translate(-1 -1)"/></svg><span class="manager-sidebar__item-text"><strong>Administración</strong></span></a><?php endif; ?>
  </div>
</nav>
