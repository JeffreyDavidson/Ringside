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
}
