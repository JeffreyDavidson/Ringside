<?php

declare(strict_types=1);

use App\Actions\Titles\CreateAction;
use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleType;
use App\Models\Titles\Title;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it creates a title with basic information', function (TitleType $titleType, string $expectedName) {
    $data = new TitleData(
        name: $expectedName,
        type: $titleType,
        debut_date: null
    );

    $result = CreateAction::run($data);

    expect($result)->toBeInstanceOf(Title::class);
    expect($result->name)->toBe($expectedName);
    expect($result->type)->toBe($titleType);

    $this->assertDatabaseHas('titles', [
        'name' => $expectedName,
        'type' => $titleType->value,
    ]);
})->with([
    [TitleType::Singles, 'WWE Championship'],
    [TitleType::TagTeam, 'Tag Team Championship'],
]);

test('it creates title with debut date', function (TitleType $titleType) {
    $debutDate = now()->subYears(3);
    $titleName = $titleType === TitleType::Singles ? 'Intercontinental Championship' : 'Women\'s Tag Team Championship';

    $data = new TitleData(
        name: $titleName,
        type: $titleType,
        debut_date: $debutDate
    );

    $result = CreateAction::run($data);

    expect($result->name)->toBe($titleName);
    expect($result->type)->toBe($titleType);

    $this->assertDatabaseHas('titles', [
        'name' => $titleName,
        'type' => $titleType->value,
    ]);
})->with([
    TitleType::Singles,
    TitleType::TagTeam,
]);

test('it creates title without debut date by default', function () {
    $titleType = fake()->randomElement(TitleType::cases());
    $titleName = $titleType === TitleType::Singles ? 'United States Championship' : 'SmackDown Tag Team Championship';

    $data = new TitleData(
        name: $titleName,
        type: $titleType,
        debut_date: null
    );

    $result = CreateAction::run($data);

    expect($result->name)->toBe($titleName);
    expect($result->type)->toBe($titleType);

    $this->assertDatabaseHas('titles', [
        'name' => $titleName,
        'type' => $titleType->value,
    ]);
});

test('it handles future debut dates', function () {
    $futureDate = now()->addDays(30);
    $titleType = fake()->randomElement(TitleType::cases());
    $titleName = $titleType === TitleType::Singles ? 'Future Championship' : 'Future Tag Championship';

    $data = new TitleData(
        name: $titleName,
        type: $titleType,
        debut_date: $futureDate
    );

    $result = CreateAction::run($data);

    expect($result->name)->toBe($titleName);
    expect($result->type)->toBe($titleType);

    $this->assertDatabaseHas('titles', [
        'name' => $titleName,
        'type' => $titleType->value,
    ]);
});
