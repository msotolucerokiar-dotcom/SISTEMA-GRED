<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="es-PE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1D375C">
    <title>Cambiar contraseña inicial | Soporte CSI · GRED Lambayeque</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/app.css">
    <style>
        body { font-family:'DM Sans',sans-serif; }
        h1 { font-family:'Manrope','DM Sans',sans-serif; }
        .focus-ring:focus-visible { outline:3px solid #3a699b;outline-offset:3px; }
    </style>
</head>
<body class="min-h-screen bg-paper text-ink">
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:text-ink">Saltar al contenido</a>
<header class="bg-ink text-white"><div class="mx-auto flex max-w-3xl items-center gap-3 px-5 py-4"><span class="grid h-11 w-11 place-items-center rounded-xl bg-white text-institutional-800" aria-hidden="true"><svg viewBox="0 0 32 32" class="h-7 w-7" fill="none"><path d="M5 25V7h4l7 10 7-10h4v18h-5V15l-6 8-6-8v10H5Z" fill="currentColor"/><circle cx="26" cy="25" r="3" fill="#D9AF32"/></svg></span><span><span class="block text-sm font-bold tracking-wide">CSI · GRED</span><span class="block text-xs text-white/80">Soporte tecnológico · GRED Lambayeque</span></span></div></header>
<main id="contenido" class="mx-auto max-w-xl px-4 py-8 sm:px-5 sm:py-12">
    <p class="text-xs font-bold uppercase tracking-wider text-institutional-700">Primer ingreso</p>
    <h1 class="mt-2 text-3xl font-extrabold">Cambia tu contraseña inicial</h1>
    <p class="mt-3 text-base leading-7 text-slate-600">Para proteger tu cuenta, escribe la contraseña que recibiste y elige una nueva. Después podrás entrar a tu panel.</p>
    <form action="/change-password" method="post" class="mt-7 space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:p-8">
        <input type="hidden" name="_csrf" value="<?= $e($session->csrfToken()) ?>">
        <?php if (isset($formErrors['form'])): ?><p role="alert" class="rounded-lg bg-rose-50 p-3 text-sm font-semibold text-rose-900"><?= $e($formErrors['form']) ?></p><?php endif; ?>
        <div><label for="current_password" class="block text-sm font-semibold">Contraseña inicial</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password" aria-invalid="<?= isset($formErrors['current']) ? 'true' : 'false' ?>" <?= isset($formErrors['current']) ? 'aria-describedby="current-error"' : '' ?> class="focus-ring mt-2 min-h-12 w-full rounded-lg border border-slate-300 px-4 text-base"><?php if (isset($formErrors['current'])): ?><p id="current-error" role="alert" class="mt-2 text-sm font-semibold text-rose-700"><?= $e($formErrors['current']) ?></p><?php endif; ?></div>
        <div><label for="new_password" class="block text-sm font-semibold">Nueva contraseña</label><p id="new-help" class="mt-1 text-sm text-slate-600">Entre 12 y 128 caracteres. Debe ser distinta de la inicial.</p><input id="new_password" name="new_password" type="password" required minlength="12" maxlength="128" autocomplete="new-password" aria-invalid="<?= isset($formErrors['new']) ? 'true' : 'false' ?>" aria-describedby="new-help<?= isset($formErrors['new']) ? ' new-error' : '' ?>" class="focus-ring mt-2 min-h-12 w-full rounded-lg border border-slate-300 px-4 text-base"><?php if (isset($formErrors['new'])): ?><p id="new-error" role="alert" class="mt-2 text-sm font-semibold text-rose-700"><?= $e($formErrors['new']) ?></p><?php endif; ?></div>
        <div><label for="confirmation" class="block text-sm font-semibold">Confirma la nueva contraseña</label><input id="confirmation" name="confirmation" type="password" required minlength="12" maxlength="128" autocomplete="new-password" aria-invalid="<?= isset($formErrors['confirmation']) ? 'true' : 'false' ?>" <?= isset($formErrors['confirmation']) ? 'aria-describedby="confirmation-error"' : '' ?> class="focus-ring mt-2 min-h-12 w-full rounded-lg border border-slate-300 px-4 text-base"><?php if (isset($formErrors['confirmation'])): ?><p id="confirmation-error" role="alert" class="mt-2 text-sm font-semibold text-rose-700"><?= $e($formErrors['confirmation']) ?></p><?php endif; ?></div>
        <button type="submit" class="focus-ring min-h-12 w-full rounded-lg bg-institutional-700 px-6 font-bold text-white hover:bg-institutional-800">Guardar contraseña y continuar</button>
    </form>
    <form action="/logout" method="post" class="mt-4 text-center"><input type="hidden" name="_csrf" value="<?= $e($session->csrfToken()) ?>"><button type="submit" class="focus-ring min-h-11 rounded-lg px-4 text-sm font-semibold text-institutional-700 underline">Cerrar sesión</button></form>
</main>
</body>
</html>
