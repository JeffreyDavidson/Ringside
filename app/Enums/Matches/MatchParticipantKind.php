<?php

declare(strict_types=1);

namespace App\Enums\Matches;

/**
 * The kinds of record a match booking names in its messages. Never stored.
 */
enum MatchParticipantKind: string
{
    case Wrestler = 'wrestler';
    case TagTeam = 'tag_team';
    case Referee = 'referee';
    case Title = 'title';

    public function label(): string
    {
        return __("matches.participant.{$this->value}.singular");
    }

    public function pluralLabel(): string
    {
        return __("matches.participant.{$this->value}.plural");
    }
}
