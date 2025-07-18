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
            expect(fn() => $this->strategy->validate($title))->not()->toThrow();
        } else {
            expect(fn() => $this->strategy->validate($title))
                ->toThrow(CannotBeRetiredException::class);
        }
    })->with([
        // Can retire: titles in various states
        ['active', true],
        ['inactive', true],
        ['undebuted', true],
        
        // Cannot retire: already retired
        ['retired', false],
    ]);

    test('validates title with active championship', function () {
        $championshipScenario = createChampionshipScenario('wrestler');
        
        // Title with active championship should still be retirable
        // (championship will be ended as part of retirement process)
        expect(fn() => $this->strategy->validate($championshipScenario['title']))
            ->not()->toThrow();
            
        expectValidChampionshipState(
            $championshipScenario['champion'], 
            $championshipScenario['title']
        );
    });
});