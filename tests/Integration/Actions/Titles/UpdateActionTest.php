<?php

declare(strict_types=1);

use App\Actions\Titles\UpdateAction;
use App\Data\Titles\TitleData;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it updates title with new information', function () {
    $title = Title::factory()->create([
        'name' => 'Original Championship',
        'introduction_date' => now()->subYears(5),
    ]);

    $updateData = new TitleData(
        name: 'Updated Championship',
        introduction_date: now()->subYears(3)
    );

    $result = UpdateAction::run($title, $updateData);

    expect($result)->toBeInstanceOf(Title::class);
    expect($result->id)->toBe($title->id);
    expect($result->name)->toBe('Updated Championship');
    expect($result->introduction_date->equalTo(now()->subYears(3)))->toBeTrue();

    $this->assertDatabaseHas('titles', [
        'id' => $title->id,
        'name' => 'Updated Championship',
        'introduction_date' => now()->subYears(3)->toDateString(),
    ]);
});

test('it updates title and creates activation when activation date provided', function () {
    $title = Title::factory()->create([
        'name' => 'Inactive Title',
    ]);

    expect($title->isActive())->toBeFalse();

    $activationDate = now();
    $updateData = new TitleData(
        name: 'Now Active Title',
        introduction_date: $title->introduction_date,
        activation_date: $activationDate
    );

    $result = UpdateAction::run($title, $updateData);

    expect($result->name)->toBe('Now Active Title');
    expect($result->isActive())->toBeTrue();

    $this->assertDatabaseHas('titles', [
        'id' => $title->id,
        'name' => 'Now Active Title',
    ]);

    $this->assertDatabaseHas('titles_activations', [
        'title_id' => $title->id,
        'activated_at' => $activationDate->toDateTimeString(),
        'deactivated_at' => null,
    ]);
});

test('it updates title without affecting existing activation', function () {
    $title = Title::factory()->active()->create([
        'name' => 'Active Title',
    ]);

    expect($title->isActive())->toBeTrue();

    $updateData = new TitleData(
        name: 'Still Active Title',
        introduction_date: $title->introduction_date
    );

    $result = UpdateAction::run($title, $updateData);

    expect($result->name)->toBe('Still Active Title');
    expect($result->isActive())->toBeTrue(); // Should remain active

    $this->assertDatabaseHas('titles', [
        'id' => $title->id,
        'name' => 'Still Active Title',
    ]);
});

test('it updates introduction date correctly', function () {
    $originalDate = now()->subYears(10);
    $newDate = now()->subYears(8);
    
    $title = Title::factory()->create([
        'name' => 'Championship',
        'introduction_date' => $originalDate,
    ]);

    $updateData = new TitleData(
        name: 'Championship',
        introduction_date: $newDate
    );

    $result = UpdateAction::run($title, $updateData);

    expect($result->introduction_date->equalTo($newDate))->toBeTrue();

    $this->assertDatabaseHas('titles', [
        'id' => $title->id,
        'introduction_date' => $newDate->toDateString(),
    ]);
});

test('it preserves other title properties during update', function () {
    $title = Title::factory()->active()->create([
        'name' => 'Test Championship',
        'introduction_date' => now()->subYears(5),
    ]);

    $originalId = $title->id;
    $originalCreatedAt = $title->created_at;

    $updateData = new TitleData(
        name: 'Updated Test Championship',
        introduction_date: $title->introduction_date
    );

    $result = UpdateAction::run($title, $updateData);

    expect($result->id)->toBe($originalId);
    expect($result->created_at->equalTo($originalCreatedAt))->toBeTrue();
    expect($result->name)->toBe('Updated Test Championship');
});