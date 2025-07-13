<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Wrestlers\Wrestler;
use App\ValueObjects\Height;

/**
 * Unit tests for WrestlerFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods (unemployed, employed, injured, suspended, retired, etc.)
 * - Factory relationship creation (withManagers, withTagTeams, etc.)
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the WrestlerFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Wrestlers\WrestlerFactory
 */

describe('wrestler factory states', function () {
    test('wrestler factory creates wrestler with realistic data', function () {
        $wrestler = Wrestler::factory()->make();

        expect((string) $wrestler->name)->toBeString();
        expect($wrestler->height)->toBeInstanceOf(Height::class);
        expect($wrestler->weight)->toBeGreaterThan(0);
        expect($wrestler->hometown)->toBeString();
        expect($wrestler->status)->toBeInstanceOf(EmploymentStatus::class);
    });

    test('wrestler factory can create unemployed wrestlers', function () {
        $wrestler = Wrestler::factory()->unemployed()->create();

        expect($wrestler->status)->toBe(EmploymentStatus::Unemployed);
        expect($wrestler->employments)->toBeEmpty();
    });

    test('wrestler factory can create employed wrestlers', function () {
        $wrestler = Wrestler::factory()->employed()->create();

        $wrestler->load('currentEmployment');
        expect($wrestler->currentEmployment)->not->toBeNull();
        expect($wrestler->currentEmployment->ended_at)->toBeNull();
    });

    test('wrestler factory can create suspended wrestlers', function () {
        $wrestler = Wrestler::factory()->suspended()->create();

        $wrestler->load('currentSuspension');
        expect($wrestler->currentSuspension)->not->toBeNull();
        expect($wrestler->currentSuspension->ended_at)->toBeNull();
    });

    test('wrestler factory can create retired wrestlers', function () {
        $wrestler = Wrestler::factory()->retired()->create();

        $wrestler->load('currentRetirement');
        expect($wrestler->currentRetirement)->not->toBeNull();
    });

    test('wrestler factory can create released wrestlers', function () {
        $wrestler = Wrestler::factory()->released()->create();

        expect($wrestler->status)->toBe(EmploymentStatus::Released);
        expect($wrestler->previousEmployments)->not->toBeEmpty();
    });

    test('wrestler factory can create wrestlers with future employment', function () {
        $wrestler = Wrestler::factory()->withFutureEmployment()->create();

        expect($wrestler->status)->toBe(EmploymentStatus::FutureEmployment);
        expect($wrestler->futureEmployment)->not->toBeNull();
    });

    test('wrestler factory can create bookable wrestlers', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        expect($wrestler->status)->toBe(EmploymentStatus::Employed);
        expect($wrestler->currentEmployment)->not->toBeNull();
    });

    test('wrestler factory can create wrestlers on tag teams', function () {
        $wrestler = Wrestler::factory()->onCurrentTagTeam()->create();

        expect($wrestler->currentTagTeam)->not->toBeNull();
    });
});
