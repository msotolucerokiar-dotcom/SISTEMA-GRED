<?php

declare(strict_types=1);

namespace App\Application\Authentication;

use App\Domain\Authentication\User;

final readonly class LoginResult
{
    private function __construct(public ?User $user, public bool $rateLimited)
    {
    }

    public static function authenticated(User $user): self
    {
        return new self($user, false);
    }

    public static function rejected(bool $rateLimited = false): self
    {
        return new self(null, $rateLimited);
    }

    public function succeeded(): bool
    {
        return $this->user !== null;
    }
}
