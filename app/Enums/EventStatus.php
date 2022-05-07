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
    public $colors = [
        'past' => 'dark',
        'scheduled' => 'success',
        'unscheduled' => 'danger',
    ];

    protected static function labels(): array
    {
        return [
            'past' => 'Past',
            'scheduled' => 'Scheduled',
            'unscheduled' => 'Unscheduled',
        ];
    }

    public function getBadgeColor()
    {
        return $this->colors[$this->value];
    }
}
