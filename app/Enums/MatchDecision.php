<?php

declare(strict_types=1);

namespace App\Enums;

enum MatchDecision: string
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
    case ReverseDecision = 'reverse-decision';

    /**
     * Get the human-readable label for the match decision.
     */
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
            self::ReverseDecision => 'Reverse Decision',
        };
    }

    /**
     * Get the decisions that result in no outcome (no winners or losers).
     *
     * @return array<MatchDecision>
     */
    private static function getNoOutcomeDecisions(): array
    {
        return [
            self::TimeLimitDraw,
            self::NoDecision,
            self::ReverseDecision,
        ];
    }

    /**
     * Check if this decision type produces winners.
     */
    public function hasWinners(): bool
    {
        return ! in_array($this, self::getNoOutcomeDecisions(), true);
    }

    /**
     * Check if this decision type produces losers.
     */
    public function hasLosers(): bool
    {
        return ! in_array($this, self::getNoOutcomeDecisions(), true);
    }

    /**
     * Check if this decision results in no winners or losers.
     */
    public function hasNoOutcome(): bool
    {
        return in_array($this, self::getNoOutcomeDecisions(), true);
    }

    /**
     * Get all match decisions as an array for forms/selects.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
