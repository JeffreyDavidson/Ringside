<?php

declare(strict_types=1);

namespace App\Enums\Storylines;

enum StorylineStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return __("storylines.{$this->value}");
    }
}
