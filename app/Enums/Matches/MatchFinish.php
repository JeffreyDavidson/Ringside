<?php

declare(strict_types=1);

namespace App\Enums\Matches;

use App\Enums\Concerns\ProvidesFilterOptions;

enum MatchFinish: string
{
    use ProvidesFilterOptions;

    case Pinfall = 'pinfall';
    case Submission = 'submission';
    case Disqualification = 'disqualification';
    case Countout = 'countout';
    case Knockout = 'knockout';
    case Stipulation = 'stipulation';
    case Forfeit = 'forfeit';
    case TimeLimitDraw = 'time-limit-draw';
    case NoDecision = 'no-decision';

    public function label(): string
    {
        return __("matches.finish.{$this->value}");
    }

    public function requiresWinningSide(): bool
    {
        return match ($this) {
            self::TimeLimitDraw, self::NoDecision => false,
            default => true,
        };
    }

    public function allowsTitleChange(): bool
    {
        return match ($this) {
            self::Pinfall,
            self::Submission,
            self::Knockout,
            self::Stipulation,
            self::Forfeit => true,
            self::Disqualification,
            self::Countout,
            self::TimeLimitDraw,
            self::NoDecision => false,
        };
    }
}
