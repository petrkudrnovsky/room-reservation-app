<?php

namespace App\Api\Model\Links;

final class ReservationLinks
{
    public function __construct(
        public readonly ?string $roomUrl,
        public readonly ?string $approvedByUrl,
        public readonly ?string $reservedForUrl,
        public readonly array $visitorsUrls,
    ) {}
}
