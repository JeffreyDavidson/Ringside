<?php

declare(strict_types=1);

namespace App\Enums\Crew;

enum CrewRole: string
{
    case Announcer = 'announcer';
    case Commentator = 'commentator';
    case Sound = 'sound';
    case Camera = 'camera';
    case RingCrew = 'ring_crew';
    case ShowCaller = 'show_caller';
    case Other = 'other';

    public function label(): string
    {
        return __('production.roles.'.$this->value);
    }
}
