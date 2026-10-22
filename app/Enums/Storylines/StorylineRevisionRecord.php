<?php

declare(strict_types=1);

namespace App\Enums\Storylines;

use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineDevelopment;
use App\Models\Storylines\StorylineParticipant;
use App\Models\Storylines\StorylineStep;

/** The kind of record a storyline revision describes, stored as a short name so a class rename never strands old rows. */
enum StorylineRevisionRecord: string
{
    case Storyline = 'storyline';
    case Step = 'step';
    case Participant = 'participant';
    case Development = 'development';

    public static function for(Storyline|StorylineStep|StorylineParticipant|StorylineDevelopment $record): self
    {
        return match (true) {
            $record instanceof Storyline => self::Storyline,
            $record instanceof StorylineStep => self::Step,
            $record instanceof StorylineParticipant => self::Participant,
            $record instanceof StorylineDevelopment => self::Development,
        };
    }
}
