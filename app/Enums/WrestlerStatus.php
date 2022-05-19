<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * These are the statuses a wrestler can have at any given time.
 *
 * @method static self bookable()
 * @method static self injured()
 * @method static self future_employment()
 * @method static self released()
 * @method static self retired()
 * @method static self suspended()
 * @method static self unemployed()
 */
class WrestlerStatus extends BaseEnum
{
    public $colors = [
        'bookable' => 'success',
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
            'bookable' => 'Bookable',
            'injured' => 'Injured',
            'future_employment' => 'Awaiting Employment',
            'released' => 'Released',
            'retired' => 'Retired',
            'suspended' => 'Suspended',
            'unemployed' => 'Unemployed',
        ];
    }
}
