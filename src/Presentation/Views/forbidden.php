<?php

declare(strict_types=1);

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="es-PE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso restringido | Soporte CSI</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="grid min-h-screen place-items-center bg-paper p-5 text-ink">
    <main class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-card">
        <p class="text-sm font-bold text-rose-700">Error 403</p>
        <h1 class="mt-2 text-2xl font-extrabold">Acceso restringido</h1>
        <p class="mt-3 text-slate-600"><?= $e($error ?? 'No tienes permisos para esta página.') ?></p>
        <?php if ($user !== null): ?>
            <p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                Sesión actual: <strong><?= $e($user->email) ?></strong><br>
                Rol: <strong><?= $e($user->roleName) ?></strong>
            </p>
        <?php endif; ?>
        <div class="mt-6 flex flex-wrap gap-3">
            <a class="inline-flex min-h-11 items-center rounded-lg bg-institutional-700 px-4 font-bold text-white hover:bg-institutional-800" href="/dashboard">Ir a mi panel</a>
            <?php if ($user !== null): ?>
                <form action="/logout" method="post">
                    <input type="hidden" name="_csrf" value="<?= $e($session->csrfToken()) ?>">
                    <button type="submit" class="min-h-11 cursor-pointer rounded-lg border border-slate-300 px-4 font-semibold text-ink hover:bg-slate-50">Cambiar de cuenta</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
