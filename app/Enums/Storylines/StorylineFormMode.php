<?php

declare(strict_types=1);

namespace App\Enums\Storylines;

enum StorylineFormMode: string
{
    case Story = 'story';
    case Step = 'step';
    case Development = 'development';
    case Participant = 'participant';
    case ParticipantEnd = 'participant_end';
    case StepStatus = 'step_status';
    case StoryStatus = 'story_status';
    case Retract = 'retract';

    public function label(): string
    {
        return __("storylines.form_titles.{$this->value}");
    }
}
