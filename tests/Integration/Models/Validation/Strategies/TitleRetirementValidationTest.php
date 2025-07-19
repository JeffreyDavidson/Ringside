<?php

declare(strict_types=1);

use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Validation\Strategies\TitleRetirementValidation;
use App\Models\Titles\Title;

/**
 * Integration tests for TitleRetirementValidation strategy.
 *
 * Tests retirement validation rules for titles with real database models.
 * Title retirement has different rules than employment-based entities.
 *
 * @see TitleRetirementValidation
 */
describe('TitleRetirementValidation', function () {
    beforeEach(function () {
        $this->strategy = new TitleRetirementValidation();
    });

    test('validates title retirement rules correctly', function ($factoryState, $shouldPass) {
        $title = Title::factory()->{$factoryState}()->create();
        
        if ($shouldPass) {
            expect(fn() => $this->strategy->validate($title))->not()->toThrow(CannotBeRetiredException::class);
        } else {
            expect(fn() => $this->strategy->validate($title))
                ->toThrow(CannotBeRetiredException::class);
        }
    })->with([
        // Can retire: titles that have been active
        ['active', true],
        ['inactive', true],
        
        // Cannot retire: titles that never debuted or are already retired
        ['undebuted', false],
        ['unactivated', false],
        ['retired', false],
    ]);

    test('validates title with active championship', function () {
        // Create an active title (must have been debuted to have championships)
        $title = Title::factory()->active()->create();
        
        // Title with active championship should still be retirable
        // (championship will be ended as part of retirement process)
        expect(fn() => $this->strategy->validate($title))
            ->not()->toThrow(CannotBeRetiredException::class);
    });
});