<?php

declare(strict_types=1);

namespace App\Data\Tournaments;

use App\Models\Tournaments\TournamentEntry;
use App\Models\Tournaments\TournamentMatchLink;
use App\Models\Tournaments\TournamentNode;

readonly class TournamentBoutData
{
    public function __construct(public TournamentNode $node, public ?TournamentEntry $first, public ?TournamentEntry $second, public bool $inputsResolved, public ?TournamentMatchLink $link, public ?TournamentEntry $winner, public bool $complete, public bool $bye) {}
}
