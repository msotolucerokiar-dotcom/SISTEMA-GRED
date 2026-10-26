<?php

declare(strict_types=1);

namespace App\Application\Authentication;

final class Logout
{
    /** Session cleanup is performed by the HTTP session adapter. */
    public function execute(): void
    {
    }
}
