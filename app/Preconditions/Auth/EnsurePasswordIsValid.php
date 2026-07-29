<?php

namespace App\Preconditions\Auth;

use DomainException;
use Illuminate\Contracts\Hashing\Hasher;

/** Проверка: пароль совпадает с хешем. */
final readonly class EnsurePasswordIsValid
{
    public function __construct(
        private Hasher $hasher,
    ) {}

    public function ensure(string $plain, string $hashed): void
    {
        if (! $this->hasher->check($plain, $hashed)) {
            throw new DomainException('Неверный email или пароль.', 422);
        }
    }
}
