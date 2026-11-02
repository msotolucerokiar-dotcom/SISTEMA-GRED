<?php

declare(strict_types=1);

$workflow = [
    ['Resumen operativo', 'Observa cifras y estados del período.', '#manager-overview', '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>'],
    ['Solicitudes por atender', 'Localiza los casos que necesitan intervención.', '#manager-attention', '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5h6M8 10h8M8 14h8M8 18h5"/>'],
    ['Clasifica y asigna', 'Define categoría, prioridad y responsable.', $managerAttention === [] ? '/csi/requests?attention=needed' : '#manager-attention-actions', '<circle cx="8" cy="8" r="3"/><path d="M3 20c0-3 2-5 5-5s5 2 5 5M17 8v8m-4-4h8"/>'],
    ['Supervisa el progreso', 'Revisa avances, tareas y reasignaciones.', '#manager-follow-up', '<path d="M3 17h4l3-6 4 4 3-8 4 2"/><path d="M3 21h18"/>'],
    ['Resultados e historial', 'Consulta reportes y el detalle de cada caso.', '/csi/reports', '<path d="M4 20V11m5 9V6m5 14v-8m5 8V4"/><path d="M2 20h20"/>'],
];
?>
<nav class="mt-10" aria-label="Recorrido habitual del Responsable CSI">
    <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-institutional-700">Guía de trabajo</p><h2 class="mt-2 text-2xl font-extrabold">De la solicitud al resultado</h2></div><p class="text-sm text-slate-600">Selecciona un paso para ir a la sección correspondiente.</p></div>
    <ol class="manager-route mt-5 grid gap-3 lg:grid-cols-5">
        <?php foreach ($workflow as $index => [$title, $description, $url, $icon]): ?>
            <li class="manager-route__item">
                <a href="<?= $e($url) ?>" class="manager-route__link block rounded-2xl border border-slate-200 bg-white p-5 text-ink shadow-card">
                    <span class="flex items-center justify-between gap-3"><span class="grid h-11 w-11 place-items-center rounded-xl bg-institutional-50 text-institutional-700"><svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icon ?></svg></span><span class="text-sm font-bold text-institutional-600"><?= sprintf('%02d', $index + 1) ?></span></span>
                    <span class="mt-4 block text-base font-bold"><?= $e($title) ?></span>
                    <span class="mt-2 block text-sm leading-6 text-slate-600"><?= $e($description) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
