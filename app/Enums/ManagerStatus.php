<?php

namespace App\Enums;

/**
 * These are the statuses a manager can have at any given time.
 *
 * @method static self available()
 * @method static self injured()
 * @method static self future_employment()
 * @method static self released()
 * @method static self retired()
 * @method static self suspended()
 * @method static self unemployed()
 */
class ManagerStatus extends BaseEnum
{
    public $colors = [
        'available' => 'success',
        'injured' => 'light',
        'future-employment' => 'warning',
        'released' => 'dark',
        'retired' => 'secondary',
        'suspended' => 'danger',
        'unemployed' => 'info',
    ];

    protected static function labels(): array
    {
        return [
            'available' => 'Available',
            'injured' => 'Injured',
            'future_employment' => 'Awaiting Employment',
            'released' => 'Released',
            'retired' => 'Retired',
            'suspended' => 'Suspended',
            'unemployed' => 'Unemployed',
        ];
    }

    public function getBadgeColor()
    {
        return $this->colors[$this->value];
    }
}
