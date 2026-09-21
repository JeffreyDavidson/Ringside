<?php

declare(strict_types=1);

namespace App\Livewire\Matches\Enums;

use App\Enums\MatchType;

enum CompetitorSelectionLayout: string
{
    case Singles = 'singles';
    case TagTeam = 'tag-team';
    case TripleThreat = 'triple-threat';
    case FatalFourWay = 'fatal-four-way';
    case BattleRoyal = 'battle-royal';
    case Generic = 'generic';

    public static function forMatchType(MatchType $matchType): self
    {
        return match ($matchType) {
            MatchType::Singles => self::Singles,
            MatchType::TagTeam,
            MatchType::SixManTagTeam,
            MatchType::EightManTagTeam,
            MatchType::TenManTagTeam,
            MatchType::TornadoTagTeam => self::TagTeam,
            MatchType::TripleThreat,
            MatchType::Triangle => self::TripleThreat,
            MatchType::Fatal4Way => self::FatalFourWay,
            MatchType::BattleRoyal,
            MatchType::RoyalRumble => self::BattleRoyal,
            default => self::Generic,
        };
    }
}
