<?php

declare(strict_types=1);

namespace App\Enums\Lifecycle;

enum LifecycleDimension: string
{
    case Activity = 'activity';
    case Deletion = 'deletion';
    case Employment = 'employment';
    case Injury = 'injury';
    case Retirement = 'retirement';
    case Suspension = 'suspension';
}
