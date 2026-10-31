<?php

declare(strict_types=1);

use App\Domain\Authentication\UserRepository;
use App\Infrastructure\Bootstrap\ContainerFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This command can only be run from CLI.\n");
    exit(1);
}

[$script, $email, $fullName, $roleCode] = array_pad($argv, 4, null);
if ($email === null || $fullName === null || $roleCode === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php bin/user-create.php email full-name (worker|technician|csi_manager|intern)\n");
    exit(2);
}

if (!in_array($roleCode, ['worker', 'technician', 'csi_manager', 'intern'], true)) {
    fwrite(STDERR, "Invalid role. Allowed roles: worker, technician, csi_manager, intern.\n");
    exit(2);
}

fwrite(STDOUT, 'New password (minimum 12 characters): ');
if (function_exists('shell_exec') && DIRECTORY_SEPARATOR !== '\\') {
    shell_exec('stty -echo');
}
$password = fgets(STDIN);
if (function_exists('shell_exec') && DIRECTORY_SEPARATOR !== '\\') {
    shell_exec('stty echo');
    fwrite(STDOUT, "\n");
}
$password = is_string($password) ? rtrim($password, "\r\n") : '';
if (strlen($password) < 12) {
    fwrite(STDERR, "Password must contain at least 12 characters.\n");
    exit(2);
}

$repository = ContainerFactory::build()->get(UserRepository::class);
$id = $repository->create(mb_strtolower($email), $fullName, password_hash($password, PASSWORD_ARGON2ID), $roleCode);
fwrite(STDOUT, "Created user #{$id} ({$email}) with role {$roleCode}.\n");
