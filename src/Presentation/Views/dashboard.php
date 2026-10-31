<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$csrfToken = $session->csrfToken();
$roleIntro = match ($user->roleCode) {
    'worker' => 'Consulta tus solicitudes y mantente al tanto de su atención.',
    'technician' => 'Revisa tu espacio de trabajo y las solicitudes que te sean asignadas.',
    'csi_manager' => 'Supervisa la atención y administra el servicio de soporte.',
    default => 'Bienvenido al portal de soporte.',
};
?>
<!doctype html>
<html lang="es-PE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mi panel | Soporte CSI</title><link rel="stylesheet" href="/assets/app.css"></head>
<body class="min-h-screen bg-paper text-ink">
<header class="bg-ink text-white"><div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4"><a href="/" class="text-sm font-bold">CSI · GRED <span class="ml-2 font-normal text-white/70">Mi panel</span></a><form action="/logout" method="post"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><button class="min-h-11 rounded-lg border border-white/30 px-4 text-sm font-semibold hover:bg-white/10">Cerrar sesión</button></form></div></header>
<main class="mx-auto max-w-6xl px-5 py-10 sm:py-14"><p class="text-sm font-bold uppercase tracking-wider text-institutional-700">Panel · <?= $e($user->roleName) ?></p><h1 class="mt-3 text-3xl font-extrabold sm:text-4xl">Hola, <?= $e($user->fullName) ?></h1><p class="mt-3 max-w-2xl text-slate-600"><?= $e($roleIntro) ?></p>
<section class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Accesos de tu rol">
<?php if ($user->can('request.create')): ?><article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card"><span class="text-xs font-bold uppercase tracking-wider text-institutional-700">Trabajador</span><h2 class="mt-3 text-lg font-bold">Nueva solicitud</h2><p class="mt-2 text-sm leading-6 text-slate-600">Registra un problema técnico para iniciar su seguimiento.</p><a href="/requests/new" class="mt-4 inline-flex min-h-11 items-center rounded-lg bg-institutional-700 px-4 text-sm font-bold text-white hover:bg-institutional-800">Registrar solicitud</a></article><?php endif; ?>
<?php if ($user->can('request.view_own')): ?><article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card"><span class="text-xs font-bold uppercase tracking-wider text-institutional-700">Trabajador</span><h2 class="mt-3 text-lg font-bold">Mis solicitudes</h2><p class="mt-2 text-sm leading-6 text-slate-600">Consulta códigos, estados y evidencias de tus casos.</p><a href="/requests" class="mt-4 inline-flex min-h-11 items-center rounded-lg border border-institutional-700 px-4 text-sm font-bold text-institutional-700 hover:bg-institutional-50">Ver mis solicitudes</a></article><?php endif; ?>
<?php if ($user->can('request.view_assigned')): ?><article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card"><span class="text-xs font-bold uppercase tracking-wider text-institutional-700">Personal técnico</span><h2 class="mt-3 text-lg font-bold">Solicitudes asignadas</h2><p class="mt-2 text-sm leading-6 text-slate-600">Aquí aparecerá tu cola de atención con evidencias y estado.</p><span class="mt-4 inline-flex rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600">Disponible en el siguiente sprint</span></article><?php endif; ?>
<?php if ($user->can('request.view_all')): ?><article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card"><span class="text-xs font-bold uppercase tracking-wider text-institutional-700">Responsable CSI</span><h2 class="mt-3 text-lg font-bold">Supervisión de solicitudes</h2><p class="mt-2 text-sm leading-6 text-slate-600">Consulta todos los casos, clasifica prioridades y asigna responsables.</p><a href="/csi/requests" class="mt-4 inline-flex min-h-11 items-center rounded-lg bg-institutional-700 px-4 text-sm font-bold text-white hover:bg-institutional-800">Abrir supervisión</a></article><?php endif; ?>
</section><section class="mt-8 rounded-2xl border border-institutional-100 bg-institutional-50 p-5"><h2 class="font-bold">Permisos de tu cuenta</h2><ul class="mt-3 flex flex-wrap gap-2"><?php foreach ($user->permissions as $permission): ?><li class="rounded-full border border-institutional-100 bg-white px-3 py-1.5 text-xs font-medium text-institutional-800"><?= $e($permission) ?></li><?php endforeach; ?></ul></section></main>
</body></html>
