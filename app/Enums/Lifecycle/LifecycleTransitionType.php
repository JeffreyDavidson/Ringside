<?php

declare(strict_types=1);

namespace App\Enums\Lifecycle;

enum LifecycleTransitionType: string
{
    case Debuted = 'debuted';
    case Deleted = 'deleted';
    case Disbanded = 'disbanded';
    case Employed = 'employed';
    case Established = 'established';
    case ClearedFromInjury = 'cleared_from_injury';
    case Injured = 'injured';
    case Merged = 'merged';
    case LegacyStatusChanged = 'legacy_status_changed';
    case Pulled = 'pulled';
    case Reinstated = 'reinstated';
    case Restored = 'restored';
    case Released = 'released';
    case Reunited = 'reunited';
    case Retired = 'retired';
    case Split = 'split';
    case Suspended = 'suspended';
    case Unretired = 'unretired';
}
