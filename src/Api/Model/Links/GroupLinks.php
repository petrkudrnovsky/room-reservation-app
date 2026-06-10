<?php

namespace App\Api\Model\Links;

final class GroupLinks
{
    public function __construct(
        public readonly array $members,
        public readonly array $admins,
        public readonly array $rooms,
    ) {}
}
