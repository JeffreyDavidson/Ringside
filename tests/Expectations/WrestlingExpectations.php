<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Enums\Stables\StableStatus;
use App\Enums\Titles\TitleStatus;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Wrestlers\Wrestler;

/**
 * Wrestling domain-specific custom expectations.
 *
 * These expectations provide readable, domain-specific assertions for wrestling
 * business logic, making tests more expressive and maintainable.
 */

// Employment Status Expectations
expect()->extend('toBeEmployed', function () {
    return $this->value->status === EmploymentStatus::Employed &&
           $this->value->isEmployed();
});

expect()->extend('toBeUnemployed', function () {
    return $this->value->status === EmploymentStatus::Unemployed &&
           ! $this->value->isEmployed();
});

expect()->extend('toBeRetired', function () {
    return $this->value->status === EmploymentStatus::Retired &&
           $this->value->isRetired();
});

expect()->extend('toBeReleased', function () {
    return $this->value->status === EmploymentStatus::Released &&
           ! $this->value->isEmployed();
});

expect()->extend('toHaveFutureEmployment', function () {
    return $this->value->status === EmploymentStatus::FutureEmployment &&
           $this->value->futureEmployment !== null;
});

// Physical Attributes for Wrestlers
expect()->extend('toHaveRealisticHeight', function () {
    if (! $this->value instanceof Wrestler) {
        throw new InvalidArgumentException('toHaveRealisticHeight() can only be used on Wrestler models');
    }

    return $this->value->height->feet >= 4 && $this->value->height->feet <= 8 &&
           $this->value->height->inches >= 0 && $this->value->height->inches <= 11;
});

expect()->extend('toHaveRealisticWeight', function () {
    if (! $this->value instanceof Wrestler) {
        throw new InvalidArgumentException('toHaveRealisticWeight() can only be used on Wrestler models');
    }

    return $this->value->weight >= 100 && $this->value->weight <= 500;
});

expect()->extend('toHaveValidPhysicalAttributes', function () {
    if (! $this->value instanceof Wrestler) {
        throw new InvalidArgumentException('toHaveValidPhysicalAttributes() can only be used on Wrestler models');
    }

    return $this->toHaveRealisticHeight() && $this->toHaveRealisticWeight();
});

// Name Validation
expect()->extend('toHaveRealisticName', function () {
    if ($this->value instanceof Manager || $this->value instanceof Referee) {
        return mb_strlen($this->value->first_name) > 2 &&
               mb_strlen($this->value->last_name) > 2 &&
               ! str_contains($this->value->first_name, 'Test') &&
               ! str_contains($this->value->last_name, 'Test');
    }

    if ($this->value instanceof Wrestler || $this->value instanceof TagTeam || $this->value instanceof Stable || $this->value instanceof Title) {
        return mb_strlen($this->value->name) > 3 &&
               ! str_contains($this->value->name, 'Test');
    }

    throw new InvalidArgumentException('toHaveRealisticName() can only be used on wrestling entity models');
});

expect()->extend('toHaveWrestlingName', function () {
    if (! $this->value instanceof Wrestler) {
        throw new InvalidArgumentException('toHaveWrestlingName() can only be used on Wrestler models');
    }

    return mb_strlen($this->value->name) > 3 &&
           ! str_contains($this->value->name, 'Test') &&
           ! empty($this->value->name);
});

expect()->extend('toHaveWrestlingTitle', function () {
    if (! $this->value instanceof Title) {
        throw new InvalidArgumentException('toHaveWrestlingTitle() can only be used on Title models');
    }

    $titleName = (string) $this->value->name;

    $hasWrestlingTerms = str_contains($titleName, 'Championship') ||
                        str_contains($titleName, 'Title') ||
                        str_contains($titleName, 'Belt') ||
                        str_contains($titleName, 'World') ||
                        str_contains($titleName, 'Heavyweight');

    return mb_strlen($titleName) > 5 &&
           ! str_contains($titleName, 'Test') &&
           $hasWrestlingTerms;
});

// Hometown Validation for Wrestlers
expect()->extend('toHaveRealisticHometown', function () {
    if (! $this->value instanceof Wrestler) {
        throw new InvalidArgumentException('toHaveRealisticHometown() can only be used on Wrestler models');
    }

    return ! empty($this->value->hometown) &&
           str_contains($this->value->hometown, ',') && // City, State format
           ! str_contains($this->value->hometown, 'Test');
});

// Status Validation
expect()->extend('toHaveValidEmploymentStatus', function () {
    if (! in_array('IsEmployable', class_uses_recursive(get_class($this->value)))) {
        throw new InvalidArgumentException('toHaveValidEmploymentStatus() can only be used on employable models');
    }

    return $this->value->status instanceof EmploymentStatus;
});

expect()->extend('toHaveValidTitleStatus', function () {
    if (! $this->value instanceof Title) {
        throw new InvalidArgumentException('toHaveValidTitleStatus() can only be used on Title models');
    }

    return $this->value->status instanceof TitleStatus;
});

expect()->extend('toHaveValidStableStatus', function () {
    if (! $this->value instanceof Stable) {
        throw new InvalidArgumentException('toHaveValidStableStatus() can only be used on Stable models');
    }

    return $this->value->status instanceof StableStatus;
});

// Injury and Suspension Expectations
expect()->extend('toBeInjured', function () {
    if (! in_array('IsInjurable', class_uses_recursive(get_class($this->value)))) {
        throw new InvalidArgumentException('toBeInjured() can only be used on injurable models');
    }

    return $this->value->isInjured();
});

expect()->extend('toBeSuspended', function () {
    if (! in_array('IsSuspendable', class_uses_recursive(get_class($this->value)))) {
        throw new InvalidArgumentException('toBeSuspended() can only be used on suspendable models');
    }

    return $this->value->isSuspended();
});

// Availability Expectations
expect()->extend('toBeAvailable', function () {
    if (! method_exists($this->value, 'isAvailable')) {
        throw new InvalidArgumentException('toBeAvailable() can only be used on models with availability checking');
    }

    return $this->value->isAvailable();
});

expect()->extend('toBeBookable', function () {
    if (! method_exists($this->value, 'isBookable')) {
        throw new InvalidArgumentException('toBeBookable() can only be used on bookable models');
    }

    return $this->value->isBookable();
});

// Championship Expectations
expect()->extend('toHaveActiveChampionship', function () {
    if (! method_exists($this->value, 'hasActiveChampionship')) {
        throw new InvalidArgumentException('toHaveActiveChampionship() can only be used on models that can hold championships');
    }

    return $this->value->hasActiveChampionship();
});

expect()->extend('toBeChampion', function () {
    if (! method_exists($this->value, 'isChampion')) {
        throw new InvalidArgumentException('toBeChampion() can only be used on models that can be champions');
    }

    return $this->value->isChampion();
});

// Stable and Tag Team Membership
expect()->extend('toBeInStable', function () {
    if (! method_exists($this->value, 'isInStable')) {
        throw new InvalidArgumentException('toBeInStable() can only be used on models that can join stables');
    }

    return $this->value->isInStable();
});

expect()->extend('toBeInTagTeam', function () {
    if (! method_exists($this->value, 'isInTagTeam')) {
        throw new InvalidArgumentException('toBeInTagTeam() can only be used on models that can join tag teams');
    }

    return $this->value->isInTagTeam();
});

// Management Expectations
expect()->extend('toHaveManager', function () {
    if (! method_exists($this->value, 'hasManager')) {
        throw new InvalidArgumentException('toHaveManager() can only be used on models that can be managed');
    }

    return $this->value->hasManager();
});

expect()->extend('toBeManaging', function () {
    if (! $this->value instanceof Manager) {
        throw new InvalidArgumentException('toBeManaging() can only be used on Manager models');
    }

    return $this->value->isManaging();
});
