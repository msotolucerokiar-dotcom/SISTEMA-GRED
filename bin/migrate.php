<?php

declare(strict_types=1);

use App\Infrastructure\Bootstrap\ContainerFactory;
use App\Infrastructure\Persistence\MigrationRunner;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = ContainerFactory::build();
$runner = $container->get(MigrationRunner::class);
$runner->migrate(dirname(__DIR__) . '/database/migrations', dirname(__DIR__) . '/database/seeds');

fwrite(STDOUT, "Database migrations and reference data are up to date.\n");
