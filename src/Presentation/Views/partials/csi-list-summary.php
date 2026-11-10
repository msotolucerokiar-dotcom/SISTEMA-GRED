<?php
declare(strict_types=1);
$metricQuery = array_intersect_key($query, array_flip(['from', 'to']));
$metricUrl = static fn (array $filters): string => '/csi/requests?' . http_build_query([...$metricQuery, ...$filters]);
$urgentId = (int) (array_column($filterOptions['priorities'], 'id', 'code')['urgent'] ?? 0);
$metricCards = [
    ['Solicitudes activas', $listMetrics['active'], $metricUrl(['open' => '1']), 'total'],
    ['Sin asignar', $listMetrics['unassigned'], $metricUrl(['assignment' => 'unassigned', 'open' => '1']), 'unassigned'],
    ['En atención', $listMetrics['in_progress'], $metricUrl(['status' => (int) ($statusIds['in_progress'] ?? 0)]), 'progress'],
    ['Por cerrar', $listMetrics['resolved'], $metricUrl(['status' => (int) ($statusIds['resolved'] ?? 0)]), 'resolved'],
];
?>
<section class="csi-list-summary" aria-label="Resumen de solicitudes">
<?php foreach ($metricCards as [$label, $count, $url, $style]): ?>
<a href="<?= $e($url) ?>" class="csi-list-summary__card csi-list-summary__card--<?= $style ?>">
<span class="csi-list-summary__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php if ($style === 'progress'): ?><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/><?php elseif ($style === 'resolved'): ?><rect x="5" y="3" width="14" height="18" rx="2"/><path d="m8 12 2 2 5-5"/><?php elseif ($style === 'unassigned'): ?><circle cx="8" cy="8" r="3"/><circle cx="16" cy="9" r="2"/><path d="M2 20c0-4 2-6 6-6s6 2 6 6M15 15c3 0 5 2 5 5"/><?php else: ?><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h6"/><?php endif; ?></svg></span>
<span class="csi-list-summary__content"><strong><?= (int) $count ?></strong><span class="csi-list-summary__label"><?= $e($label) ?></span><small><?= $e(match ($style) { 'total' => 'Solicitudes que aún no han finalizado', 'progress' => 'Atenciones técnicas en ejecución', 'resolved' => 'Solucionadas pendientes de cierre', default => 'Solicitudes sin responsable técnico' }) ?></small></span>
</a>
<?php endforeach; ?>
</section>
