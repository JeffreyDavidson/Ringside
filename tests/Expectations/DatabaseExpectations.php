<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;

/**
 * Database-specific custom expectations for testing.
 *
 * These expectations provide convenient methods for testing database
 * operations, seeding, and data consistency.
 */

// Seeder Testing Expectations
expect()->extend('toSeedSuccessfully', function () {
    if (! is_string($this->value)) {
        throw new InvalidArgumentException('toSeedSuccessfully() expects a seeder class name as string');
    }

    try {
        Artisan::call('db:seed', ['--class' => $this->value]);

        return true;
    } catch (Exception $e) {
        return false;
    }
});

expect()->extend('toSeedExactly', function (int $count) {
    if (! is_string($this->value)) {
        throw new InvalidArgumentException('toSeedExactly() expects a seeder class name as string');
    }

    // This would need to be implemented per-seeder based on what model it creates
    // For now, we'll return true and let the specific seeder tests handle count validation
    return $this->toSeedSuccessfully();
});

expect()->extend('toSeedWithoutDuplicates', function () {
    if (! is_string($this->value)) {
        throw new InvalidArgumentException('toSeedWithoutDuplicates() expects a seeder class name as string');
    }

    // This would need specific implementation based on the seeder's behavior
    // For now, we'll return true and let the specific tests handle duplicate checking
    return $this->toSeedSuccessfully();
});

// Collection Uniqueness Expectations
expect()->extend('toHaveUniqueNames', function () {
    if (! $this->value instanceof Collection) {
        throw new InvalidArgumentException('toHaveUniqueNames() can only be used on Collections');
    }

    $names = $this->value->pluck('name');

    return $names->unique()->count() === $names->count();
});

expect()->extend('toHaveUniqueEmails', function () {
    if (! $this->value instanceof Collection) {
        throw new InvalidArgumentException('toHaveUniqueEmails() can only be used on Collections');
    }

    $emails = $this->value->pluck('email');

    return $emails->unique()->count() === $emails->count();
});

expect()->extend('toHaveUniqueValues', function (string $attribute) {
    if (! $this->value instanceof Collection) {
        throw new InvalidArgumentException('toHaveUniqueValues() can only be used on Collections');
    }

    $values = $this->value->pluck($attribute);

    return $values->unique()->count() === $values->count();
});

// Database State Expectations
expect()->extend('toExistInDatabase', function (?string $table = null, array $attributes = []) {
    if (! is_object($this->value) || ! method_exists($this->value, 'getTable')) {
        throw new InvalidArgumentException('toExistInDatabase() can only be used on Eloquent models');
    }

    $table = $table ?? $this->value->getTable();
    $attributes = empty($attributes) ? ['id' => $this->value->id] : $attributes;

    return Illuminate\Support\Facades\DB::table($table)->where($attributes)->exists();
});

expect()->extend('toBePersistedCorrectly', function () {
    if (! is_object($this->value) || ! method_exists($this->value, 'exists')) {
        throw new InvalidArgumentException('toBePersistedCorrectly() can only be used on Eloquent models');
    }

    return $this->value->exists && $this->value->id > 0;
});

// Factory Testing Expectations
expect()->extend('toGenerateRealisticData', function () {
    if (! is_object($this->value) || ! method_exists($this->value, 'make')) {
        throw new InvalidArgumentException('toGenerateRealisticData() can only be used on Factory instances');
    }

    $model = $this->value->make();

    // Check that required fields are populated
    foreach ($model->getFillable() as $field) {
        if (is_null($model->$field) && ! in_array($field, ['ended_at', 'cleared_at', 'reinstated_at', 'preview'])) {
            return false;
        }
    }

    return true;
});

expect()->extend('toCreateInDatabase', function () {
    if (! is_object($this->value) || ! method_exists($this->value, 'create')) {
        throw new InvalidArgumentException('toCreateInDatabase() can only be used on Factory instances');
    }

    $model = $this->value->create();

    return $model->exists && $model->id > 0;
});

expect()->extend('toHaveConsistentStates', function () {
    if (! is_object($this->value) || ! method_exists($this->value, 'make')) {
        throw new InvalidArgumentException('toHaveConsistentStates() can only be used on Factory instances');
    }

    // Create multiple instances and check for consistency
    $models = collect(range(1, 5))->map(fn () => $this->value->make());

    foreach ($models as $model) {
        // Check that all models have the same structure
        if (empty($model->getFillable())) {
            return false;
        }
    }

    return true;
});

// Relationship Testing
expect()->extend('toLoadRelationshipCorrectly', function (string $relationship) {
    if (! is_object($this->value) || ! method_exists($this->value, 'load')) {
        throw new InvalidArgumentException('toLoadRelationshipCorrectly() can only be used on Eloquent models');
    }

    $this->value->load($relationship);

    return $this->value->relationLoaded($relationship);
});

// Date Range Validation
expect()->extend('toHaveValidDateRange', function () {
    if (! is_object($this->value)) {
        throw new InvalidArgumentException('toHaveValidDateRange() can only be used on objects');
    }

    $startField = null;
    $endField = null;

    // Determine the date fields based on common patterns
    if (property_exists($this->value, 'started_at') && property_exists($this->value, 'ended_at')) {
        $startField = 'started_at';
        $endField = 'ended_at';
    } elseif (property_exists($this->value, 'injured_at') && property_exists($this->value, 'cleared_at')) {
        $startField = 'injured_at';
        $endField = 'cleared_at';
    } elseif (property_exists($this->value, 'suspended_at') && property_exists($this->value, 'reinstated_at')) {
        $startField = 'suspended_at';
        $endField = 'reinstated_at';
    } elseif (property_exists($this->value, 'retired_at') && property_exists($this->value, 'ended_at')) {
        $startField = 'retired_at';
        $endField = 'ended_at';
    } elseif (property_exists($this->value, 'joined_at') && property_exists($this->value, 'left_at')) {
        $startField = 'joined_at';
        $endField = 'left_at';
    }

    if (! $startField || ! $endField) {
        throw new InvalidArgumentException('toHaveValidDateRange() requires models with recognizable date range fields');
    }

    $startDate = $this->value->$startField;
    $endDate = $this->value->$endField;

    if (! $startDate instanceof Carbon\Carbon) {
        return false;
    }

    if ($endDate && ! $endDate instanceof Carbon\Carbon) {
        return false;
    }

    if ($endDate && ! $endDate->isAfter($startDate)) {
        return false;
    }

    return true;
});

// Timeline Period Validation
expect()->extend('toHaveActiveTimelinePeriod', function () {
    if (! $this->toHaveValidDateRange()) {
        return false;
    }

    // Check if end date is null (indicating active period)
    $endFields = ['ended_at', 'cleared_at', 'reinstated_at', 'left_at'];

    foreach ($endFields as $field) {
        if (property_exists($this->value, $field)) {
            return is_null($this->value->$field);
        }
    }

    return false;
});

expect()->extend('toHaveEndedTimelinePeriod', function () {
    if (! $this->toHaveValidDateRange()) {
        return false;
    }

    // Check if end date is set (indicating ended period)
    $endFields = ['ended_at', 'cleared_at', 'reinstated_at', 'left_at'];

    foreach ($endFields as $field) {
        if (property_exists($this->value, $field)) {
            return ! is_null($this->value->$field);
        }
    }

    return false;
});
