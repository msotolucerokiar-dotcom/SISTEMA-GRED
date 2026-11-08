<?php
declare(strict_types=1);
$value = static fn (string $key): string => is_string($_GET[$key] ?? null) ? $_GET[$key] : '';
$activeFilters = [];
$filterLabels = ['search' => 'Búsqueda', 'status' => 'Estado', 'priority' => 'Prioridad', 'technician' => 'Técnico', 'area' => 'Oficina', 'category' => 'Categoría', 'assignment' => 'Asignación', 'attention' => 'Atención', 'validation' => 'Validación', 'open' => 'Situación', 'from' => 'Desde', 'to' => 'Hasta'];
foreach ($filterLabels as $key => $label) {
    if ($value($key) === '') { continue; }
    $display = $value($key);
    foreach (['status' => 'statuses', 'priority' => 'priorities', 'technician' => 'technicians', 'area' => 'areas', 'category' => 'categories'] as $optionKey => $source) {
        if ($key !== $optionKey) { continue; }
        foreach ($filterOptions[$source] as $option) { if ((string) $option['id'] === $value($key)) { $display = $option['name']; break; } }
    }
    $display = match ($key) { 'assignment' => $display === 'assigned' ? 'Asignadas' : 'Sin asignar', 'attention' => 'Por asignar o clasificar', 'validation' => 'Pendientes de validar', 'open' => 'Abiertas', default => $display };
    $removeQuery = $_GET;
    unset($removeQuery[$key], $removeQuery['page']);
    $activeFilters[] = ['label' => $label . ': ' . $display, 'url' => '/csi/requests' . ($removeQuery === [] ? '' : '?' . http_build_query($removeQuery))];
}
?>
<form method="get" action="/csi/requests" class="csi-list-filters" aria-label="Filtros de solicitudes">
  <div class="csi-list-filters__primary">
    <div class="csi-list-search"><label for="search">Buscar solicitudes</label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input id="search" name="search" type="search" maxlength="100" value="<?= $e($value('search')) ?>" placeholder="Buscar por código, asunto o trabajador..."></div>
    <?php foreach (['priority' => ['Prioridad', 'priorities'], 'category' => ['Clasificación', 'categories'], 'technician' => ['Responsable', 'technicians'], 'status' => ['Estado', 'statuses']] as $key => [$label, $source]): ?><div class="csi-list-field"><label for="<?= $key ?>"><?= $label ?></label><select id="<?= $key ?>" name="<?= $key ?>"><option value=""><?= $key === 'priority' || $key === 'category' ? 'Todas' : 'Todos' ?></option><?php foreach ($filterOptions[$source] as $option): ?><option value="<?= (int) $option['id'] ?>" <?= $value($key) === (string) $option['id'] ? 'selected' : '' ?>><?= $e($option['name']) ?></option><?php endforeach; ?></select></div><?php endforeach; ?>
  </div>
  <details class="csi-list-advanced" <?= array_filter(['area', 'assignment', 'attention', 'validation', 'open', 'from', 'to'], static fn ($key) => $value($key) !== '') !== [] ? 'open' : '' ?>><summary><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 7h18M6 12h12M9 17h6"/><circle cx="8" cy="7" r="2" fill="white"/><circle cx="15" cy="12" r="2" fill="white"/></svg>Más filtros <span aria-hidden="true">⌄</span></summary><div class="csi-list-advanced__grid">
    <div class="csi-list-field"><label for="area">Área</label><select id="area" name="area"><option value="">Todas</option><?php foreach ($filterOptions['areas'] as $option): ?><option value="<?= (int) $option['id'] ?>" <?= $value('area') === (string) $option['id'] ? 'selected' : '' ?>><?= $e($option['name']) ?></option><?php endforeach; ?></select></div>
    <div class="csi-list-field"><label for="assignment">Asignación</label><select id="assignment" name="assignment"><option value="">Todas</option><option value="unassigned" <?= $value('assignment') === 'unassigned' ? 'selected' : '' ?>>Sin asignar</option><option value="assigned" <?= $value('assignment') === 'assigned' ? 'selected' : '' ?>>Asignadas</option></select></div>
    <div class="csi-list-field"><label for="attention">Atención requerida</label><select id="attention" name="attention"><option value="">Todas</option><option value="needed" <?= $value('attention') === 'needed' ? 'selected' : '' ?>>Por asignar o clasificar</option></select></div>
    <div class="csi-list-field"><label for="validation">Validación de tareas</label><select id="validation" name="validation"><option value="">Todas</option><option value="pending" <?= $value('validation') === 'pending' ? 'selected' : '' ?>>Pendientes de validar</option></select></div>
    <div class="csi-list-field"><label for="open">Situación</label><select id="open" name="open"><option value="">Todas</option><option value="1" <?= $value('open') === '1' ? 'selected' : '' ?>>Abiertas</option></select></div>
    <div class="csi-list-field"><label for="from">Desde</label><input id="from" name="from" type="date" value="<?= $e($value('from')) ?>"></div>
    <div class="csi-list-field"><label for="to">Hasta</label><input id="to" name="to" type="date" value="<?= $e($value('to')) ?>"></div>
  </div></details>
  <button type="submit" class="csi-list-filter-button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 5h18l-7 8v5l-4 2v-7L3 5Z"/></svg>Filtrar</button>
  <?php if ($filterErrors !== []): ?><p role="alert" class="csi-list-filter-error"><?= $e(implode(' ', $filterErrors)) ?></p><?php endif; ?>
  <?php if ($activeFilters !== []): ?><div class="csi-list-active-filters"><strong>Filtros aplicados:</strong><?php foreach ($activeFilters as $filter): ?><a href="<?= $e($filter['url']) ?>" aria-label="Quitar filtro <?= $e($filter['label']) ?>"><?= $e($filter['label']) ?> <span aria-hidden="true">×</span></a><?php endforeach; ?><a class="csi-list-clear" href="/csi/requests">Limpiar filtros</a></div><?php endif; ?>
</form>
