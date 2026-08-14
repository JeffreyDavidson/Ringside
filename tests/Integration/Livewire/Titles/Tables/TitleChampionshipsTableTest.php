<?php

declare(strict_types=1);

use App\Livewire\Titles\Tables\TitleChampionshipsTable;
use App\Models\Titles\TitleChampionship;

test('displays championship reign length from its dates', function () {
    $championship = new TitleChampionship([
        'won_at' => '2025-01-01',
        'lost_at' => '2025-01-11',
    ]);

    $reignLengthColumn = (new TitleChampionshipsTable())->columns()[3];

    expect($reignLengthColumn->resolveValue($championship))->toBe('10');
});
