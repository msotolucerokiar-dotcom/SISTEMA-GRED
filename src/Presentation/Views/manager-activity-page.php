<?php
declare(strict_types=1);
$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="es-PE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Historial de actividad | CSI GRED</title><link rel="stylesheet" href="/assets/app.css?v=<?= (int) filemtime(dirname(__DIR__, 3) . '/public/assets/app.css') ?>"></head>
<body class="manager-panel min-h-screen bg-paper text-ink"><a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-white focus:p-3">Ir al contenido</a>
<header class="bg-institutional-800 text-white"><div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4"><a href="/dashboard" class="font-bold">CSI · GRED</a><a href="/dashboard" class="rounded-lg border border-white/40 px-4 py-2 text-sm font-semibold hover:bg-white/10">Volver al panel</a></div></header>
<main id="contenido" class="mx-auto max-w-5xl px-5 py-8 sm:py-12">
  <?php require __DIR__ . '/partials/manager-follow-up.php'; ?>
  <?php if ($activityPages > 1): ?><nav class="mt-6 flex items-center justify-between gap-4 text-sm" aria-label="Páginas del historial"><?php if ($activityPage > 1): ?><a class="font-semibold text-institutional-700 underline" href="/csi/activity?page=<?= $activityPage - 1 ?>">Anterior</a><?php else: ?><span></span><?php endif; ?><span>Página <?= $activityPage ?> de <?= $activityPages ?></span><?php if ($activityPage < $activityPages): ?><a class="font-semibold text-institutional-700 underline" href="/csi/activity?page=<?= $activityPage + 1 ?>">Siguiente</a><?php else: ?><span></span><?php endif; ?></nav><?php endif; ?>
</main></body></html>
