<?php

declare(strict_types=1);

namespace App\Enums\Titles;

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Relations\Relation;

enum TitleType: string
{
    case Singles = 'singles';
    case TagTeam = 'tag-team';

    /** @return class-string<Wrestler|TagTeam> */
    public function championModelClass(): string
    {
        return match ($this) {
            self::Singles => Wrestler::class,
            self::TagTeam => TagTeam::class,
        };
    }

    public function championMorphClass(): int|string
    {
        return Relation::getMorphAlias($this->championModelClass());
    }
}
