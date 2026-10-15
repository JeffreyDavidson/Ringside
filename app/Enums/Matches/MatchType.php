<?php

declare(strict_types=1);

namespace App\Enums\Matches;

use App\Enums\Concerns\ProvidesFilterOptions;
use App\Enums\Lifecycle\LifecycleOwnerType;

enum MatchType: string
{
    use ProvidesFilterOptions;

    case Singles = 'singles';
    case TagTeam = 'tag-team';
    case TripleThreat = 'triple-threat';
    case Triangle = 'triangle';
    case Fatal4Way = 'fatal-4-way';
    case SixManTagTeam = '6-man-tag-team';
    case EightManTagTeam = '8-man-tag-team';
    case TenManTagTeam = '10-man-tag-team';
    case TwoOnOneHandicap = 'two-on-one-handicap';
    case ThreeOnTwoHandicap = 'three-on-two-handicap';
    case BattleRoyal = 'battle-royal';
    case RoyalRumble = 'royal-rumble';
    case TornadoTagTeam = 'tornado-tag-team';
    case Gauntlet = 'gauntlet';

    public function label(): string
    {
        return __("matches.type.{$this->value}");
    }

    public function numberOfSides(): ?int
    {
        return match ($this) {
            self::Singles, self::TagTeam, self::SixManTagTeam, self::EightManTagTeam,
            self::TenManTagTeam, self::TwoOnOneHandicap, self::ThreeOnTwoHandicap,
            self::TornadoTagTeam, self::Gauntlet => 2,
            self::TripleThreat, self::Triangle => 3,
            self::Fatal4Way => 4,
            self::BattleRoyal, self::RoyalRumble => null,
        };
    }

    /**
     * Get the allowed competitor types as an array.
     *
     * @return array<string>
     */
    public function getAllowedCompetitorTypes(): array
    {
        return match ($this) {
            self::TagTeam, self::TornadoTagTeam, self::SixManTagTeam,
            self::EightManTagTeam, self::TenManTagTeam, self::TripleThreat,
            self::Fatal4Way, self::TwoOnOneHandicap, self::ThreeOnTwoHandicap,
            self::Gauntlet => [LifecycleOwnerType::Wrestler->value, LifecycleOwnerType::TagTeam->value],
            default => [LifecycleOwnerType::Wrestler->value],
        };
    }

    /** @return list<int>|null */
    public function requiredRosterMembersPerSide(): ?array
    {
        return match ($this) {
            self::TagTeam, self::TornadoTagTeam => [2, 2],
            self::SixManTagTeam => [3, 3],
            self::EightManTagTeam => [4, 4],
            self::TenManTagTeam => [5, 5],
            self::TwoOnOneHandicap => [1, 2],
            self::ThreeOnTwoHandicap => [2, 3],
            default => null,
        };
    }

    /** @return list<int>|null */
    public function requiredCompetitorEntriesPerSide(): ?array
    {
        return match ($this) {
            self::Singles => [1, 1],
            self::TripleThreat, self::Triangle => [1, 1, 1],
            self::Fatal4Way => [1, 1, 1, 1],
            default => null,
        };
    }

    public function usesIndividualCompetitorSides(): bool
    {
        return in_array($this, [self::BattleRoyal, self::RoyalRumble], true);
    }

    /**
     * Check if this match type allows wrestler competitors.
     */
    public function allowsWrestlers(): bool
    {
        return in_array(LifecycleOwnerType::Wrestler->value, $this->getAllowedCompetitorTypes(), true);
    }

    /**
     * Check if this match type allows tag team competitors.
     */
    public function allowsTagTeams(): bool
    {
        return in_array(LifecycleOwnerType::TagTeam->value, $this->getAllowedCompetitorTypes(), true);
    }

    /**
     * Get the minimum number of competitors required for this match type.
     */
    public function getMinimumCompetitors(): int
    {
        return match ($this) {
            self::BattleRoyal => 3,
            self::RoyalRumble => 10,
            default => $this->numberOfSides() ?? 2,
        };
    }

    /**
     * Get the maximum number of competitors allowed for this match type.
     */
    public function getMaximumCompetitors(): ?int
    {
        return match ($this) {
            self::BattleRoyal => null,
            self::RoyalRumble => 30,
            default => $this->getMinimumCompetitors(),
        };
    }
}
