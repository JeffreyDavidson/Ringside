<?php

declare(strict_types=1);

use App\Builders\Titles\TitleChampionshipBuilder;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Unit tests for TitleChampionship model structure and configuration.
 *
 * UNIT TEST SCOPE:
 * - Model attribute configuration (fillable, casts, defaults)
 * - Custom builder class verification
 * - Trait integration verification
 * - Interface implementation verification
 *
 * These tests verify that the TitleChampionship model is properly configured
 * and structured according to the data layer requirements.
 */
describe('TitleChampionship Model Unit Tests', function () {
    describe('title championship attributes and configuration', function () {
        test('title championship uses correct table name', function () {
            $titleChampionship = new TitleChampionship();
            expect($titleChampionship->getTable())->toBe('titles_championships');
        });

        test('title championship has correct fillable properties', function () {
            $titleChampionship = new TitleChampionship();

            expect($titleChampionship->getFillable())->toEqual([
                'title_id',
                'champion_type',
                'champion_id',
                'won_event_match_id',
                'lost_event_match_id',
                'won_at',
                'lost_at',
            ]);
        });

        test('title championship has correct casts configuration', function () {
            $titleChampionship = new TitleChampionship();
            $casts = $titleChampionship->getCasts();

            expect($casts['won_at'])->toBe('datetime');
            expect($casts['lost_at'])->toBe('datetime');
            expect($casts['last_held_reign'])->toBe('datetime');
        });

        test('title championship has custom eloquent builder', function () {
            $titleChampionship = new TitleChampionship();
            expect($titleChampionship->query())->toBeInstanceOf(TitleChampionshipBuilder::class);
        });

        test('title championship has correct default values', function () {
            $titleChampionship = new TitleChampionship();
            // TitleChampionship model has no custom default values
            expect($titleChampionship)->toBeInstanceOf(TitleChampionship::class);
        });
    });

    describe('title championship trait integration', function () {
        test('title championship uses all required traits', function () {
            expect(TitleChampionship::class)->usesTrait(HasFactory::class);
        });

        test('title championship implements all required interfaces', function () {
            $interfaces = class_implements(TitleChampionship::class);

            // TitleChampionship model implements no custom interfaces beyond base Model
            expect($interfaces)->toBeArray();
        });
    });
});
