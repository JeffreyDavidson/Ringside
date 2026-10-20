<?php

declare(strict_types=1);

namespace App\Enums\Storylines;

enum StorylineRevisionOperation: string
{
    case Created = 'created';
    case StoryRevised = 'story_revised';
    case StoryStatus = 'story_status';
    case StepPlanned = 'step_planned';
    case StepRevised = 'step_revised';
    case StepStatus = 'step_status';
    case ParticipantJoined = 'participant_joined';
    case ParticipantLeft = 'participant_left';
    case DevelopmentRecorded = 'development_recorded';
    case DevelopmentCorrected = 'development_corrected';
    case DevelopmentRetracted = 'development_retracted';
    case DevelopmentRetractedReopen = 'development_retracted_reopen';

    public function label(): string
    {
        return __("storylines.operations.{$this->value}");
    }
}
