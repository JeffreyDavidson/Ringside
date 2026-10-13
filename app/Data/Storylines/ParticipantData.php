<?php

declare(strict_types=1);

namespace App\Data\Storylines;

readonly class ParticipantData
{
    public function __construct(public string $type, public int $id, public ?string $role, public string $joinedOn, public ?string $leftOn = null, public ?string $reason = null) {}
}
