<?php

namespace App\Filter;

final class GroupFilterCriteria
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?array $memberIds = null,
        public readonly ?array $adminIds = null,
        public readonly ?array $roomIds = null,
    ) {}
}
