<?php

declare(strict_types=1);

$actionLabels = [
    'created' => 'Solicitud registrada', 'classified' => 'Clasificación actualizada',
    'assigned' => 'Técnico asignado', 'reassigned' => 'Técnico reasignado',
    'started' => 'Atención iniciada', 'resolved' => 'Solicitud solucionada',
];
?>
<section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-card" aria-labelledby="historial"><h2 id="historial" class="text-xl font-bold">Historial de acciones</h2>
<?php if ($history === []): ?><p class="mt-3 text-slate-600">Aún no hay movimientos registrados.</p><?php else: ?><ol class="mt-5 space-y-5 border-l-2 border-institutional-100 pl-6"><?php foreach ($history as $event): ?><li class="relative"><span aria-hidden="true" class="absolute -left-[31px] top-1 h-3 w-3 rounded-full bg-institutional-700"></span><h3 class="font-bold"><?= $e($actionLabels[$event['action']] ?? 'Acción registrada') ?></h3><p class="mt-1 text-sm text-slate-600"><?= $e($event['actor_name']) ?> · <time datetime="<?= $e($event['created_at']) ?>"><?= $e($localDate($event['created_at'])) ?></time></p><?php if (is_string($event['details']['observation'] ?? null) && $event['details']['observation'] !== ''): ?><p class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-700"><?= $e($event['details']['observation']) ?></p><?php endif; ?></li><?php endforeach; ?></ol><?php endif; ?></section>
