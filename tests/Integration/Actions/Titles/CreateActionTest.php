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

test('it creates a title', function () {
    $data = new TitleData('Example Title', TitleType::Singles, null);

    $result = resolve(CreateAction::class)->handle($data);

    expect($result)->toBeInstanceOf(Title::class)
        ->and($result->name)->toBe('Example Title')
        ->and($result->type)->toBe(TitleType::Singles)
        ->and($result->activityPeriods)->toBeEmpty();
});

test('it activates a title if activation date is filled in request', function () {
    $datetime = now();
    $data = new TitleData('Example Title', TitleType::Singles, $datetime);

    $result = resolve(CreateAction::class)->handle($data);

    expect($result)->toBeInstanceOf(Title::class)
        ->and($result->name)->toBe('Example Title')
        ->and($result->type)->toBe(TitleType::Singles)
        ->and($result->activityPeriods)->toHaveCount(1)
        ->and(requiredDate($result->activityPeriods->firstOrFail()->started_at)->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});
