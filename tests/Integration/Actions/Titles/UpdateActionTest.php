<?php

declare(strict_types=1);

use App\Actions\Titles\UpdateAction;
use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleType;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it updates title with new information', function () {
    $title = Title::factory()->create([
        'name' => 'Original Championship',
        'type' => TitleType::Singles,
    ]);

    $updateData = new TitleData(
        name: 'Updated Championship',
        type: TitleType::TagTeam,
        debut_date: null
    );

    $result = UpdateAction::run($title, $updateData);

    expect($result)->toBeInstanceOf(Title::class);
    expect($result->id)->toBe($title->id);
    expect($result->name)->toBe('Updated Championship');
    expect($result->type)->toBe(TitleType::TagTeam);

    $this->assertDatabaseHas('titles', [
        'id' => $title->id,
        'name' => 'Updated Championship',
        'type' => TitleType::TagTeam->value,
    ]);
});

test('it updates title name without changing type', function () {
    $title = Title::factory()->create([
        'name' => 'WWE Championship',
        'type' => TitleType::Singles,
    ]);

    $updateData = new TitleData(
        name: 'World Heavyweight Championship',
        type: TitleType::Singles,
        debut_date: null
    );

    $result = UpdateAction::run($title, $updateData);

    expect($result->name)->toBe('World Heavyweight Championship');
    expect($result->type)->toBe(TitleType::Singles);

    $this->assertDatabaseHas('titles', [
        'id' => $title->id,
        'name' => 'World Heavyweight Championship',
        'type' => TitleType::Singles->value,
    ]);
});

test('it updates title type without changing name', function () {
    $title = Title::factory()->create([
        'name' => 'Championship Title',
        'type' => TitleType::Singles,
    ]);

    $updateData = new TitleData(
        name: 'Championship Title',
        type: TitleType::TagTeam,
        debut_date: null
    );

    $result = UpdateAction::run($title, $updateData);

    expect($result->name)->toBe('Championship Title');
    expect($result->type)->toBe(TitleType::TagTeam);

    $this->assertDatabaseHas('titles', [
        'id' => $title->id,
        'name' => 'Championship Title',
        'type' => TitleType::TagTeam->value,
    ]);
});

test('it preserves other title properties during update', function () {
    $title = Title::factory()->create([
        'name' => 'Test Championship',
        'type' => TitleType::Singles,
    ]);

    $originalId = $title->id;
    $originalCreatedAt = $title->created_at;

    $updateData = new TitleData(
        name: 'Updated Test Championship',
        type: TitleType::Singles,
        debut_date: null
    );

    $result = UpdateAction::run($title, $updateData);

    expect($result->id)->toBe($originalId);
    expect($result->created_at->equalTo($originalCreatedAt))->toBeTrue();
    expect($result->name)->toBe('Updated Test Championship');
});
