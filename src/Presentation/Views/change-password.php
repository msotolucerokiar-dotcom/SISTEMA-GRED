<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html><html lang="es-PE"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cambiar contraseña | Soporte CSI</title><link rel="stylesheet" href="/assets/app.css"></head>
<body class="min-h-screen bg-paper text-ink"><header class="bg-ink text-white"><div class="mx-auto max-w-3xl px-5 py-4"><span class="text-sm font-bold">CSI · GRED</span></div></header><main class="mx-auto max-w-xl px-4 py-10 sm:px-5"><h1 class="text-3xl font-extrabold">Cambia tu contraseña inicial</h1><p class="mt-3 text-slate-600">Antes de usar el sistema, crea una contraseña que solo tú conozcas.</p><form action="/change-password" method="post" class="mt-7 space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-8"><input type="hidden" name="_csrf" value="<?= $e($session->csrfToken()) ?>">
<?php if ($formErrors !== []): ?><p role="alert" class="rounded-lg bg-rose-50 p-3 text-sm font-semibold text-rose-900"><?= $e(implode(' ', $formErrors)) ?></p><?php endif; ?>
<div><label for="current_password" class="block font-semibold">Contraseña inicial</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="mt-2 min-h-12 w-full rounded-lg border border-slate-300 px-4 text-base"></div>
<div><label for="new_password" class="block font-semibold">Nueva contraseña</label><p class="mt-1 text-sm text-slate-600">Entre 12 y 128 caracteres.</p><input id="new_password" name="new_password" type="password" required minlength="12" maxlength="128" autocomplete="new-password" class="mt-2 min-h-12 w-full rounded-lg border border-slate-300 px-4 text-base"></div>
<div><label for="confirmation" class="block font-semibold">Confirma la nueva contraseña</label><input id="confirmation" name="confirmation" type="password" required minlength="12" maxlength="128" autocomplete="new-password" class="mt-2 min-h-12 w-full rounded-lg border border-slate-300 px-4 text-base"></div>
<button class="min-h-12 w-full rounded-lg bg-institutional-700 px-6 font-bold text-white hover:bg-institutional-800">Guardar contraseña</button></form><form action="/logout" method="post" class="mt-4"><input type="hidden" name="_csrf" value="<?= $e($session->csrfToken()) ?>"><button class="min-h-11 text-sm font-semibold text-institutional-700 underline">Cerrar sesión</button></form></main></body></html>
