<?php

namespace App\Enums;

/**
 * These are the statuses a title can have at any given time.
 *
 * @method static self active()
 * @method static self inactive()
 * @method static self future_activation()
 * @method static self retired()
 * @method static self unactivated()
 */
class TitleStatus extends BaseEnum
{
    public $colors = [
        'active' => 'success',
        'inactive' => 'light',
        'future-activation' => 'warning',
        'retired' => 'secondary',
        'unactivated' => 'info',
    ];

    protected static function labels(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'future_activation' => 'Awaiting Activation',
            'retired' => 'Retired',
            'unactivated' => 'Unactivated',
        ];
    }

    public function getBadgeColor()
    {
        return $this->colors[$this->value];
    }
}
