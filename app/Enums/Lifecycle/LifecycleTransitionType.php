<?php

declare(strict_types=1);

namespace App\Enums\Lifecycle;

enum LifecycleTransitionType: string
{
    case Debuted = 'debuted';
    case Disbanded = 'disbanded';
    case Established = 'established';
    case LegacyStatusChanged = 'legacy_status_changed';
    case Pulled = 'pulled';
    case Reinstated = 'reinstated';
    case Reunited = 'reunited';
    case Retired = 'retired';
    case Unretired = 'unretired';
}
