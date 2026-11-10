<?php
declare(strict_types=1);

$value = static fn (string $key): string => is_string($_GET[$key] ?? null) ? $_GET[$key] : '';
$activeFilters = [];
$filterLabels = ['search' => 'Búsqueda', 'area' => 'Oficina', 'technician' => 'Técnico', 'priority' => 'Prioridad', 'category' => 'Categoría', 'status' => 'Estado', 'assignment' => 'Asignación', 'attention' => 'Atención', 'validation' => 'Validación', 'alert' => 'Aviso', 'from' => 'Desde', 'to' => 'Hasta'];
foreach ($filterLabels as $key => $label) {
    if ($value($key) === '' || (in_array($key, ['from', 'to'], true) && in_array($value('period'), ['today', '7days', 'month'], true))) { continue; }
    $display = $value($key);
    foreach (['status' => 'statuses', 'priority' => 'priorities', 'technician' => 'technicians', 'area' => 'areas', 'category' => 'categories'] as $optionKey => $source) {
        if ($key !== $optionKey) { continue; }
        foreach ($filterOptions[$source] as $option) { if ((string) $option['id'] === $value($key)) { $display = $option['name']; break; } }
    }
    $display = match ($key) { 'assignment' => $display === 'assigned' ? 'Asignadas' : 'Sin asignar', 'attention' => 'Por asignar o clasificar', 'validation' => 'Pendientes de validar', 'alert' => match ($display) { 'stale' => 'Con demora (48 h sin avances)', 'missing_priority' => 'Sin prioridad', default => 'Sin categoría' }, default => $display };
    $removeQuery = $_GET;
    unset($removeQuery[$key], $removeQuery['page']);
    $activeFilters[] = ['label' => $label . ': ' . $display, 'url' => '/csi/requests' . ($removeQuery === [] ? '' : '?' . http_build_query($removeQuery))];
}
$selectedPeriod = in_array($value('period'), ['all', 'today', '7days', 'month', 'custom'], true) ? $value('period') : ($value('from') !== '' || $value('to') !== '' ? 'custom' : 'all');
?>
<form method="get" action="/csi/requests" class="csi-list-filters csi-list-filters--compact" aria-label="Filtros de solicitudes">
  <div class="csi-list-filters__primary">
    <div class="csi-list-search"><label for="search">Buscar solicitudes</label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input id="search" name="search" type="search" maxlength="100" value="<?= $e($value('search')) ?>" placeholder="Buscar por código, problema o solicitante..."></div>
    <?php foreach (['area' => ['Oficina', 'areas'], 'technician' => ['Técnico', 'technicians'], 'priority' => ['Prioridad', 'priorities'], 'category' => ['Categoría', 'categories']] as $key => [$label, $source]): ?><div class="csi-list-field"><label class="sr-only" for="<?= $key ?>"><?= $label ?></label><div class="csi-list-field__inline"><span aria-hidden="true"><?= $label ?>:</span><select id="<?= $key ?>" name="<?= $key ?>" aria-label="<?= $label ?>" onchange="this.form.requestSubmit()"><option value="">Todas</option><?php foreach ($filterOptions[$source] as $option): ?><option value="<?= (int) $option['id'] ?>" <?= $value($key) === (string) $option['id'] ? 'selected' : '' ?>><?= $e($option['name']) ?></option><?php endforeach; ?></select></div></div><?php endforeach; ?>
    <div class="csi-list-field"><label class="sr-only" for="period">Período</label><div class="csi-list-field__inline"><span aria-hidden="true">Período:</span><select id="period" name="period" aria-label="Período" onchange="this.form.requestSubmit()"><option value="all" <?= $selectedPeriod === 'all' ? 'selected' : '' ?>>Todo el tiempo</option><option value="today" <?= $selectedPeriod === 'today' ? 'selected' : '' ?>>Hoy</option><option value="7days" <?= $selectedPeriod === '7days' ? 'selected' : '' ?>>Últimos 7 días</option><option value="month" <?= $selectedPeriod === 'month' ? 'selected' : '' ?>>Este mes</option><option value="custom" <?= $selectedPeriod === 'custom' ? 'selected' : '' ?>>Personalizado</option></select></div></div>
  </div>
  <div class="csi-list-filters__secondary"><div class="csi-list-filter-state"><strong>Filtro activo: <?= ($_GET['open'] ?? '') === '1' ? 'Activas' : 'Selección actual' ?></strong><span>Combina los filtros para precisar tu consulta.</span></div><div class="csi-list-filter-sort"><label for="sort">Ordenar por:</label><select id="sort" name="sort" onchange="this.form.requestSubmit()"><option value="recent" <?= $value('sort') === '' || $value('sort') === 'recent' ? 'selected' : '' ?>>Más recientes</option><option value="oldest" <?= $value('sort') === 'oldest' ? 'selected' : '' ?>>Más antiguas</option><option value="updated_desc" <?= $value('sort') === 'updated_desc' ? 'selected' : '' ?>>Última actualización</option><option value="priority_desc" <?= $value('sort') === 'priority_desc' ? 'selected' : '' ?>>Prioridad</option></select></div><a class="csi-list-clear" href="/csi/requests">Limpiar filtros</a></div>
  <details class="csi-list-advanced"><summary>Más filtros <span aria-hidden="true">⌄</span></summary><div class="csi-list-advanced__grid">
    <div class="csi-list-field"><label for="status">Estado</label><select id="status" name="status"><option value="">Todos</option><?php foreach ($filterOptions['statuses'] as $option): ?><option value="<?= (int) $option['id'] ?>" <?= $value('status') === (string) $option['id'] ? 'selected' : '' ?>><?= $e($option['name']) ?></option><?php endforeach; ?></select></div>
    <div class="csi-list-field"><label for="assignment">Asignación</label><select id="assignment" name="assignment"><option value="">Todas</option><option value="unassigned" <?= $value('assignment') === 'unassigned' ? 'selected' : '' ?>>Sin asignar</option><option value="assigned" <?= $value('assignment') === 'assigned' ? 'selected' : '' ?>>Asignadas</option></select></div>
    <div class="csi-list-field"><label for="attention">Atención requerida</label><select id="attention" name="attention"><option value="">Todas</option><option value="needed" <?= $value('attention') === 'needed' ? 'selected' : '' ?>>Por asignar o clasificar</option></select></div>
    <div class="csi-list-field"><label for="validation">Validación de tareas</label><select id="validation" name="validation"><option value="">Todas</option><option value="pending" <?= $value('validation') === 'pending' ? 'selected' : '' ?>>Pendientes de validar</option></select></div>
    <div class="csi-list-field"><label for="from">Desde</label><input id="from" name="from" type="date" value="<?= $e($value('from')) ?>" oninput="this.form.elements.period.value='custom'"></div>
    <div class="csi-list-field"><label for="to">Hasta</label><input id="to" name="to" type="date" value="<?= $e($value('to')) ?>" oninput="this.form.elements.period.value='custom'"></div>
    <button type="submit" class="csi-list-filter-button">Aplicar filtros</button>
  </div></details>
  <button type="submit" class="sr-only focus:not-sr-only">Buscar</button>
  <?php if ($filterErrors !== []): ?><p role="alert" class="csi-list-filter-error"><?= $e(implode(' ', $filterErrors)) ?></p><?php endif; ?>
  <?php if ($activeFilters !== []): ?><div class="csi-list-active-filters"><strong>Filtros aplicados:</strong><?php foreach ($activeFilters as $filter): ?><a href="<?= $e($filter['url']) ?>" aria-label="Quitar filtro <?= $e($filter['label']) ?>"><?= $e($filter['label']) ?> <span aria-hidden="true">×</span></a><?php endforeach; ?></div><?php endif; ?>
</form>
