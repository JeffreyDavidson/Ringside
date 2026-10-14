<?php

declare(strict_types=1);

namespace App\Data\Tournaments;

use App\Enums\Tournaments\EntrantKind;

readonly class TournamentData
{
    /** @param list<int> $competitorIds */
    public function __construct(public string $name, public ?string $description, public EntrantKind $kind, public int $capacity, public bool $repeatAppearances, public array $competitorIds, public ?string $requestId = null) {}
}
