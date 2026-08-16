<?php

declare(strict_types=1);

namespace App\Enums\Roster;

enum RosterEntityType: string
{
    case Wrestler = 'wrestler';
    case Manager = 'manager';
    case Referee = 'referee';
    case TagTeam = 'tag-team';

    public function translationNamespace(): string
    {
        return match ($this) {
            self::Wrestler => 'wrestlers',
            self::Manager => 'managers',
            self::Referee => 'referees',
            self::TagTeam => 'tag-teams',
        };
    }
}
