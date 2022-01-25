<?php

namespace App\Enums;

/**
 * These are the statuses a tag team can have at any given time.
 *
 * @method static self bookable()
 * @method static self unbookable()
 * @method static self future_employment()
 * @method static self released()
 * @method static self retired()
 * @method static self suspended()
 * @method static self unemployed()
 */
class TagTeamStatus extends BaseEnum
{
}
