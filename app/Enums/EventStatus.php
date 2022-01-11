<?php

namespace App\Enums;

/**
 * These are the statuses an event can have at any given time.
 *
 * @method static self past()
 * @method static self scheduled()
 * @method static self unscheduled()
 */
final class EventStatus extends BaseEnum
{
}
