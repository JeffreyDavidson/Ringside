<?php

declare(strict_types=1);

use App\Livewire\Titles\Tables\TitleChampionshipsTable;
use App\Models\Titles\TitleChampionship;

it('displays championship reign length from its dates', function (): void {
    // Arrange
    $championship = new TitleChampionship([
        'won_at' => '2025-01-01',
        'lost_at' => '2025-01-11',
    ]);
    $table = new TitleChampionshipsTable();

    // Act
    $columns = $table->columns();
    $reignLength = $columns[3]->resolveValue($championship);

    // Assert
    expect($reignLength)->toBe('10');
});
