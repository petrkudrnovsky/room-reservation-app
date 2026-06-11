<?php

namespace App\Filter;

final class ReservationFilterCriteria
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $status = null,
        public readonly ?int $room = null,
        public readonly ?int $reservedFor = null,
        public readonly ?string $visitors = null,
    ) {}
}
