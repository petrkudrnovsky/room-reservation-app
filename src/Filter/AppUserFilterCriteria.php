<?php

namespace App\Filter;

final class AppUserFilterCriteria
{
    public function __construct(
        public readonly ?string $username = null,
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
    ) {}
}
