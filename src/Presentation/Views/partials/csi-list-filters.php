<?php
declare(strict_types=1);
$value = static fn (string $key): string => is_string($_GET[$key] ?? null) ? $_GET[$key] : '';
?>
<form method="get" action="/csi/requests" class="csi-list-filters" aria-label="Filtros de solicitudes">
  <div class="csi-list-filters__primary">
    <div class="csi-list-search"><label for="search">Buscar solicitudes</label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg><input id="search" name="search" type="search" maxlength="100" value="<?= $e($value('search')) ?>" placeholder="Buscar por código o asunto..."></div>
    <?php foreach (['status' => ['Estado', 'statuses'], 'priority' => ['Prioridad', 'priorities'], 'technician' => ['Técnico', 'technicians']] as $key => [$label, $source]): ?><div class="csi-list-field"><label for="<?= $key ?>"><?= $label ?></label><select id="<?= $key ?>" name="<?= $key ?>"><option value=""><?= $key === 'priority' ? 'Todas' : 'Todos' ?></option><?php foreach ($filterOptions[$source] as $option): ?><option value="<?= (int) $option['id'] ?>" <?= $value($key) === (string) $option['id'] ? 'selected' : '' ?>><?= $e($option['name']) ?></option><?php endforeach; ?></select></div><?php endforeach; ?>
    <button type="submit" class="csi-list-filter-button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 5h18l-7 8v5l-4 2v-7L3 5Z"/></svg>Filtrar</button>
  </div>
  <details class="csi-list-advanced" <?= array_filter(['area', 'category', 'assignment', 'attention', 'validation', 'open', 'from', 'to'], static fn ($key) => $value($key) !== '') !== [] ? 'open' : '' ?>><summary>Más filtros <span aria-hidden="true">⌄</span></summary><div class="csi-list-advanced__grid">
    <?php foreach (['area' => ['Área', 'areas'], 'category' => ['Categoría', 'categories']] as $key => [$label, $source]): ?><div class="csi-list-field"><label for="<?= $key ?>"><?= $label ?></label><select id="<?= $key ?>" name="<?= $key ?>"><option value="">Todas</option><?php foreach ($filterOptions[$source] as $option): ?><option value="<?= (int) $option['id'] ?>" <?= $value($key) === (string) $option['id'] ? 'selected' : '' ?>><?= $e($option['name']) ?></option><?php endforeach; ?></select></div><?php endforeach; ?>
    <div class="csi-list-field"><label for="assignment">Asignación</label><select id="assignment" name="assignment"><option value="">Todas</option><option value="unassigned" <?= $value('assignment') === 'unassigned' ? 'selected' : '' ?>>Sin asignar</option><option value="assigned" <?= $value('assignment') === 'assigned' ? 'selected' : '' ?>>Asignadas</option></select></div>
    <div class="csi-list-field"><label for="attention">Atención requerida</label><select id="attention" name="attention"><option value="">Todas</option><option value="needed" <?= $value('attention') === 'needed' ? 'selected' : '' ?>>Por asignar o clasificar</option></select></div>
    <div class="csi-list-field"><label for="validation">Validación de tareas</label><select id="validation" name="validation"><option value="">Todas</option><option value="pending" <?= $value('validation') === 'pending' ? 'selected' : '' ?>>Pendientes de validar</option></select></div>
    <div class="csi-list-field"><label for="open">Situación</label><select id="open" name="open"><option value="">Todas</option><option value="1" <?= $value('open') === '1' ? 'selected' : '' ?>>Abiertas</option></select></div>
    <div class="csi-list-field"><label for="from">Desde</label><input id="from" name="from" type="date" value="<?= $e($value('from')) ?>"></div>
    <div class="csi-list-field"><label for="to">Hasta</label><input id="to" name="to" type="date" value="<?= $e($value('to')) ?>"></div>
  </div></details>
  <?php if ($filterErrors !== []): ?><p role="alert" class="csi-list-filter-error"><?= $e(implode(' ', $filterErrors)) ?></p><?php endif; ?>
  <a class="csi-list-clear" href="/csi/requests">Limpiar filtros</a>
</form>
