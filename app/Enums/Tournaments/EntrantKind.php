<?php

declare(strict_types=1);

namespace App\Enums\Tournaments;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

enum EntrantKind: string
{
    case Wrestler = 'wrestler';
    case TagTeam = 'tag_team';

    public function label(): string
    {
        return __("tournaments.kind.{$this->value}");
    }

    /** @return class-string<Wrestler>|class-string<TagTeam> */
    public function modelClass(): string
    {
        return match ($this) {
            self::Wrestler => Wrestler::class, self::TagTeam => TagTeam::class
        };
    }
}
