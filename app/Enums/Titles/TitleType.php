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

    public function label(): string
    {
        return match ($this) {
            self::Singles => 'Singles',
            self::TagTeam => 'Tag Team',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

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

    public function competitorInputKey(): string
    {
        return match ($this) {
            self::Singles => 'wrestlers',
            self::TagTeam => 'tag_teams',
        };
    }

    public function opposingCompetitorInputKey(): string
    {
        return match ($this) {
            self::Singles => self::TagTeam->competitorInputKey(),
            self::TagTeam => self::Singles->competitorInputKey(),
        };
    }

    public function competitorLabel(): string
    {
        return match ($this) {
            self::Singles => 'wrestlers',
            self::TagTeam => 'tag teams',
        };
    }

    public static function tryFromChampionMorphClass(int|string $morphClass): ?self
    {
        foreach (self::cases() as $type) {
            if ($type->championMorphClass() === $morphClass) {
                return $type;
            }
        }

        return null;
    }
}
