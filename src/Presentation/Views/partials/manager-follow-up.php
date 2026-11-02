<?php

declare(strict_types=1);

$actionLabels = require __DIR__ . '/history-action-labels.php';
$localEventDate = static fn (string $value): string => (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('America/Lima'))->format('d/m/Y H:i');
$feeds = [
    'in_progress' => ['En atención', 'No hay solicitudes en atención.'],
    'updates' => ['Últimas actualizaciones', 'Todavía no hay actualizaciones registradas.'],
    'reassigned' => ['Cambios de responsable', 'No se han registrado reasignaciones.'],
    'resolved' => ['Solucionadas recientemente', 'Todavía no hay soluciones registradas.'],
    'pending_validation' => ['Tareas por validar', 'No hay trabajos de practicantes pendientes de validación.'],
];
$eventDescription = static function (string $key, array $item) use ($actionLabels): string {
    return match ($key) {
        'in_progress' => 'Responsable: ' . ($item['technician_name'] ?? 'Sin asignar'),
        'updates' => ($actionLabels[$item['action']] ?? 'Acción registrada') . ' · ' . $item['actor_name'],
        'reassigned' => ($item['previous_name'] ?? 'Responsable anterior') . ' → ' . ($item['next_name'] ?? 'Nuevo responsable'),
        'resolved' => 'Solución registrada por ' . $item['actor_name'],
        'pending_validation' => 'Trabajo de ' . $item['intern_name'] . ' · Responsable: ' . $item['responsible_name'],
        default => '',
    };
};
?>
<section class="manager-followup rounded-2xl border border-slate-200 bg-white p-4 shadow-card sm:p-6" aria-labelledby="manager-follow-up">
    <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 id="manager-follow-up" class="text-xl font-extrabold">Seguimiento de atenciones</h2><p class="mt-1 text-sm text-slate-600">Estado actual y últimos movimientos del historial del CSI.</p></div><a href="/csi/requests" class="inline-flex min-h-11 items-center text-sm font-bold text-institutional-700 underline">Ver historial completo</a></div>
    <div class="manager-followup-grid mt-4">
        <?php foreach ($feeds as $key => [$title, $emptyMessage]): ?>
            <section class="manager-followup-feed" aria-labelledby="follow-up-<?= $e($key) ?>">
                <h3 id="follow-up-<?= $e($key) ?>" class="text-sm font-bold text-institutional-700"><?= $e($title) ?></h3>
                <?php if ($managerFollowUp[$key] === []): ?>
                    <p class="mt-3 text-sm text-slate-600"><?= $e($emptyMessage) ?></p>
                <?php else: ?>
                    <ol class="mt-2 divide-y divide-slate-200">
                        <?php foreach ($managerFollowUp[$key] as $item): ?>
                            <li class="py-2">
                                <a href="/csi/requests/<?= rawurlencode($item['public_code']) ?><?= $key === 'pending_validation' ? '#tareas-colaboracion' : '' ?>" class="break-words text-sm font-bold text-institutional-700 underline"><?= $e($item['public_code']) ?> · <?= $e($item['title']) ?></a>
                                <p class="mt-1 break-words text-sm text-slate-700"><?= $e($eventDescription($key, $item)) ?></p>
                                <p class="mt-1 text-xs text-slate-600"><time datetime="<?= $e($item['event_at']) ?>"><?= $e($localEventDate($item['event_at'])) ?></time></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</section>
