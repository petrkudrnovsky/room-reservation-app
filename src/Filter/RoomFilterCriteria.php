<?php

namespace App\Filter;

final class RoomFilterCriteria
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $code = null,
        public readonly ?string $buildingCode = null,
        public readonly ?array $owningGroupIds = null,
        public readonly ?array $memberIds = null,
        public readonly ?array $adminIds = null,
    ) {}
}
