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
    $olderChampionship = TitleChampionship::factory()->for($title)->ended()->create([
        'won_at' => now()->subYears(4),
        'lost_at' => now()->subYears(3),
    ]);
    $latestChampionship = TitleChampionship::factory()->for($title)->ended()->create([
        'won_at' => now()->subYears(2),
        'lost_at' => now()->subYear(),
    ]);
    TitleChampionship::factory()->for($title)->current()->create();
    TitleChampionship::factory()->ended()->create();
    $table = new PreviousTitleChampionships();
    $table->titleId = $title->id;

    $championships = $table->builder()->get();

    expect($championships->modelKeys())->toBe([
        $latestChampionship->id,
        $olderChampionship->id,
    ]);
});
