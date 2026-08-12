<?php

declare(strict_types=1);

namespace App\Enums\Lifecycle;

enum LifecycleDimension: string
{
    case Activity = 'activity';
    case Employment = 'employment';
    case Retirement = 'retirement';
    case Suspension = 'suspension';
}
