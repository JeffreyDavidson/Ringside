<?php

declare(strict_types=1);

use App\Livewire\Titles\Tables\PreviousTitleChampionships;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

test('displays championship reign length from its dates', function () {
    $championship = new TitleChampionship([
        'won_at' => '2025-01-01',
        'lost_at' => '2025-01-11',
    ]);

    $reignLengthColumn = (new PreviousTitleChampionships())->columns()[3];

    expect($reignLengthColumn->resolveValue($championship))->toBe('10');
});

test('only retrieves previous championships for the selected title', function () {
    $title = Title::factory()->create();
    $previousChampionship = TitleChampionship::factory()->for($title)->ended()->create();
    TitleChampionship::factory()->for($title)->current()->create();
    TitleChampionship::factory()->ended()->create();
    $table = new PreviousTitleChampionships();
    $table->titleId = $title->id;

    $championships = $table->builder()->get();

    expect($championships)->toHaveCount(1)
        ->and($championships->firstOrFail()->is($previousChampionship))->toBeTrue();
});
