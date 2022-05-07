<?php

namespace App\Enums;

/**
 * These are the statuses a stable can have at any given time.
 *
 * @method static self active()
 * @method static self future_activation()
 * @method static self inactive()
 * @method static self retired()
 * @method static self unactivated()
 */
class StableStatus extends BaseEnum
{
    public $colors = [
        'active' => 'success',
        'future-activation' => 'warning',
        'inactive' => 'dark',
        'retired' => 'secondary',
        'unactivated' => 'info',
    ];

    protected static function labels(): array
    {
        return [
            'active' => 'Active',
            'future_activation' => 'Awaiting Employment',
            'inactive' => 'Inactive',
            'retired' => 'Retired',
            'unactivated' => 'Unactivated',
        ];
    }

    public function getBadgeColor()
    {
        return $this->colors[$this->value];
    }
}
