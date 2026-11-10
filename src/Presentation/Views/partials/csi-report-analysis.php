<?php
declare(strict_types=1);
$leadingCategories = array_slice($categories, 0, 2, true);
$leadingAreas = array_slice($areas, 0, 2, true);
$leadingCategoryCount = array_sum($leadingCategories);
$leadingAreaCount = array_sum($leadingAreas);
$categoryNames = implode(' y ', array_keys($leadingCategories));
$areaNames = implode(' y ', array_keys($leadingAreas));
$peakIndex = array_search(max(array_column($bins, 'received')), array_column($bins, 'received'), true);
$peakBin = $bins[$peakIndex];
$share = static fn (int $count): string => number_format(100 * $count / max(1, (int) $summary['total']), 1, ',', '') . ' %';
$leadingCategory = (string) array_key_first($categories);
?>
<section class="report-card report-analysis" aria-labelledby="report-analysis-title">
  <h2 id="report-analysis-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h6"/></svg>Análisis del período</h2>
  <p>Observaciones elaboradas a partir de los registros del período seleccionado.</p>
  <?php if ($summary['total'] === 0): ?><p class="report-analysis__empty">No hay solicitudes para analizar en este período.</p><?php else: ?>
  <div class="report-analysis__rows">
    <div class="report-analysis__row"><span class="report-analysis__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5"/></svg></span><div><strong>Concentración de incidencias</strong><p><?= $e($categoryNames) ?> <?= count($leadingCategories) === 1 ? 'reúne' : 'reúnen' ?> <?= $leadingCategoryCount ?> de <?= (int) $summary['total'] ?> solicitudes (<?= $share($leadingCategoryCount) ?>).</p></div></div>
    <div class="report-analysis__row"><span class="report-analysis__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 20h16M6 20V4h12v16M9 8h2m2 0h2M9 12h2m2 0h2M9 16h2m2 0h2"/></svg></span><div><strong>Dependencias con mayor demanda</strong><p><?= $e($areaNames) ?> <?= count($leadingAreas) === 1 ? 'registra' : 'registran' ?> <?= $leadingAreaCount ?> solicitudes (<?= $share($leadingAreaCount) ?>).</p></div></div>
    <div class="report-analysis__row"><span class="report-analysis__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 17l6-6 4 3 7-8M16 6h4v4"/></svg></span><div><strong>Variación entre intervalos</strong><p>Las solicitudes recibidas alcanzan su máximo en <?= $e($peakBin['label']) ?> (<?= (int) $peakBin['received'] ?> registros).</p></div></div>
    <div class="report-analysis__row"><span class="report-analysis__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 10v6M12 7h.01"/></svg></span><div><strong>Seguimiento recomendado</strong><p><?= $leadingCategory === 'Sin clasificar' ? 'Revisar la clasificación de las solicitudes para identificar problemas recurrentes.' : 'Revisar la recurrencia de ' . $e($leadingCategory) . ' sin atribuir causas no verificadas.' ?></p></div></div>
  </div>
  <?php endif; ?>
</section>
