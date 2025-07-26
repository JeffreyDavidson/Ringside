<?php

declare(strict_types=1);

use App\Actions\Titles\CreateAction;
use App\Data\Titles\TitleData;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it creates a title with basic information', function () {
    $data = new TitleData(
        name: 'WWE Championship',
        introduction_date: now()->subYears(5)
    );

    $result = CreateAction::run($data);

    expect($result)->toBeInstanceOf(Title::class);
    expect($result->name)->toBe('WWE Championship');
    expect($result->introduction_date->equalTo(now()->subYears(5)))->toBeTrue();

    $this->assertDatabaseHas('titles', [
        'name' => 'WWE Championship',
        'introduction_date' => now()->subYears(5)->toDateString(),
    ]);
});

test('it creates title with activation date', function () {
    $activationDate = now();
    
    $data = new TitleData(
        name: 'Intercontinental Championship',
        introduction_date: now()->subYears(3),
        activation_date: $activationDate
    );

    $result = CreateAction::run($data);

    expect($result->name)->toBe('Intercontinental Championship');
    expect($result->isActive())->toBeTrue();

    $this->assertDatabaseHas('titles', [
        'name' => 'Intercontinental Championship',
        'introduction_date' => now()->subYears(3)->toDateString(),
    ]);

    $this->assertDatabaseHas('titles_activations', [
        'title_id' => $result->id,
        'activated_at' => $activationDate->toDateTimeString(),
        'deactivated_at' => null,
    ]);
});

test('it creates title without activation by default', function () {
    $data = new TitleData(
        name: 'United States Championship',
        introduction_date: now()->subYears(2)
    );

    $result = CreateAction::run($data);

    expect($result->isActive())->toBeFalse();

    $this->assertDatabaseHas('titles', [
        'name' => 'United States Championship',
    ]);

    // Should not create activation record
    $this->assertDatabaseMissing('titles_activations', [
        'title_id' => $result->id,
    ]);
});

test('it handles future introduction dates', function () {
    $futureDate = now()->addDays(30);
    
    $data = new TitleData(
        name: 'Future Championship',
        introduction_date: $futureDate
    );

    $result = CreateAction::run($data);

    expect($result->name)->toBe('Future Championship');
    expect($result->introduction_date->equalTo($futureDate))->toBeTrue();

    $this->assertDatabaseHas('titles', [
        'name' => 'Future Championship',
        'introduction_date' => $futureDate->toDateString(),
    ]);
});

test('it creates title with same introduction and activation date', function () {
    $date = now();
    
    $data = new TitleData(
        name: 'World Heavyweight Championship',
        introduction_date: $date,
        activation_date: $date
    );

    $result = CreateAction::run($data);

    expect($result->name)->toBe('World Heavyweight Championship');
    expect($result->isActive())->toBeTrue();
    expect($result->introduction_date->equalTo($date))->toBeTrue();

    $this->assertDatabaseHas('titles', [
        'name' => 'World Heavyweight Championship',
        'introduction_date' => $date->toDateString(),
    ]);

    $this->assertDatabaseHas('titles_activations', [
        'title_id' => $result->id,
        'activated_at' => $date->toDateTimeString(),
        'deactivated_at' => null,
    ]);
});