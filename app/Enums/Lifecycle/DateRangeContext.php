<?php

declare(strict_types=1);

namespace App\Enums\Lifecycle;

/**
 * What a date range error is about, so its message names the thing in translated words.
 */
enum DateRangeContext: string
{
    case ActivityPeriod = 'activity_period';
    case DeletionPeriod = 'deletion_period';
    case EmploymentPeriod = 'employment_period';
    case InjuryPeriod = 'injury_period';
    case RetirementPeriod = 'retirement_period';
    case StableActivity = 'stable_activity';
    case StableActivityEnd = 'stable_activity_end';
    case StableEstablishment = 'stable_establishment';
    case StableMembershipRemoval = 'stable_membership_removal';
    case SuspensionPeriod = 'suspension_period';
    case TitleActivity = 'title_activity';
    case TitleActivityEnd = 'title_activity_end';

    public static function forPeriod(LifecycleDimension $dimension): self
    {
        return match ($dimension) {
            LifecycleDimension::Activity => self::ActivityPeriod,
            LifecycleDimension::Deletion => self::DeletionPeriod,
            LifecycleDimension::Employment => self::EmploymentPeriod,
            LifecycleDimension::Injury => self::InjuryPeriod,
            LifecycleDimension::Retirement => self::RetirementPeriod,
            LifecycleDimension::Suspension => self::SuspensionPeriod,
        };
    }

    public function label(): string
    {
        return __("core.date_range_contexts.{$this->value}");
    }
}
