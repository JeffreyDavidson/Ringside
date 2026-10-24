<?php

declare(strict_types=1);

namespace App\Enums\Storylines;

use App\Enums\Concerns\ProvidesFilterOptions;

enum StorylineStatus: string
{
    use ProvidesFilterOptions;

    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return __("storylines.{$this->value}");
    }
}
