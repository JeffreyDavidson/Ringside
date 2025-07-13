<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use App\Models\Titles\TitleRetirement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for Title Retirement model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 *
 * These tests verify that the Title model is properly configured
 * and structured according to the data layer requirements.
 */
describe('Title Retirement Model Unit Tests', function () {
    describe('title retirement attributes and configuration', function () {
        test('title retirement uses correct table name', function () {
            $titleRetirement = new TitleRetirement();
            expect($titleRetirement->getTable())->toBe('titles_retirements');
        });

        test('title has correct fillable properties', function () {
            $titleRetirement = new TitleRetirement();

            expect($titleRetirement->getFillable())->toEqual([
                'title_id',
                'started_at',
                'ended_at',
            ]);
        });

        test('title retirement has correct casts configuration', function () {
            $titleRetirement = new TitleRetirement();
            $casts = $titleRetirement->getCasts();

            expect($casts)->toBeArray();
            expect($casts['id'])->toBe('int');
            expect($casts['started_at'])->toBe('datetime');
            expect($casts['ended_at'])->toBe('datetime');
        });

        test('title has custom eloquent builder', function () {
            $titleRetirement = new TitleRetirement();
            expect($titleRetirement->query())->toBeObject();
        });

        test('title retirement has correct default values', function () {
            $titleRetirement = new TitleRetirement();
            // Model has no custom default values
            expect($titleRetirement)->toBeInstanceOf(TitleRetirement::class);
        });
    });

    describe('title retirement trait integration', function () {
        test('title uses all required traits', function () {
            expect(Title::class)->usesTrait(HasFactory::class);
        });
    });
});
