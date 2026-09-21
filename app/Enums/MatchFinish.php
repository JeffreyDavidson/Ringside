<?php

declare(strict_types=1);

namespace App\Enums;

enum MatchFinish: string
{
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
        return match ($this) {
            self::Pinfall => 'Pinfall',
            self::Submission => 'Submission',
            self::Disqualification => 'Disqualification',
            self::Countout => 'Countout',
            self::Knockout => 'Knockout',
            self::Stipulation => 'Stipulation',
            self::Forfeit => 'Forfeit',
            self::TimeLimitDraw => 'Time Limit Draw',
            self::NoDecision => 'No Decision',
        };
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

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $finish): array => [$finish->value => $finish->label()])
            ->all();
    }
}
