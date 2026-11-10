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
<span class="csi-list-summary__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php if ($style === 'urgent'): ?><path d="m12 3 9 17H3L12 3Z"/><path d="M12 9v5m0 3h.01"/><?php elseif ($style === 'progress'): ?><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/><?php elseif ($style === 'resolved'): ?><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/><?php elseif ($style === 'unassigned'): ?><circle cx="12" cy="8" r="3"/><path d="M5 21c0-4 2.5-7 7-7s7 3 7 7"/><?php else: ?><path d="m3 8 9-5 9 5-9 5-9-5Zm0 4 9 5 9-5M3 16l9 5 9-5"/><?php endif; ?></svg></span>
<span class="csi-list-summary__content"><strong><?= (int) $count ?></strong><span class="csi-list-summary__label"><?= $e($label) ?></span><small><?= $e(match ($style) { 'total' => 'Solicitudes que aún no han finalizado', 'progress' => 'Atenciones técnicas en ejecución', 'resolved' => 'Solucionadas pendientes de cierre', default => 'Solicitudes sin responsable técnico' }) ?></small></span>
</a>
<?php endforeach; ?>
</section>
