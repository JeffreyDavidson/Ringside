<?php

declare(strict_types=1);

namespace App\Enums;

enum BusinessRuleReason: string
{
    case AlreadyEmployed = 'already_employed';
    case AlreadyInjured = 'already_injured';
    case AlreadyRetired = 'already_retired';
    case AlreadySuspended = 'already_suspended';
    case General = 'general';
    case Injured = 'injured';
    case NotDeleted = 'not_deleted';
    case NotInjured = 'not_injured';
    case NotRetired = 'not_retired';
    case NotSuspended = 'not_suspended';
    case Retired = 'retired';
    case Suspended = 'suspended';
    case Unemployed = 'unemployed';
}
