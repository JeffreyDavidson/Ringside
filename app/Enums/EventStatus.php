<?php

namespace App\Enums;

/**
 * These are the statuses an event can have at any given time.
 *
 * @method static self past()
 * @method static self scheduled()
 * @method static self unscheduled()
 */
class EventStatus extends BaseEnum
{
    protected static function labels(): array
    {
        return [
            'past' => 'Past',
            'scheduled' => 'Scheduled',
            'unscheduled' => 'Unscheduled',
        ];
    }
}
