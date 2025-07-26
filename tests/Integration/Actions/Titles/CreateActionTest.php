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

    $result = CreateAction::run($data);

    expect($result)->toBeInstanceOf(Title::class);
    expect($result->name)->toBe('Example Title');
    expect($result->type)->toBe(TitleType::Singles);
    expect($result->activations)->toHaveCount(0);
});

test('it activates a title if activation date is filled in request', function () {
    $datetime = now();
    $data = new TitleData('Example Title', TitleType::Singles, $datetime);

    $result = CreateAction::run($data);

    expect($result)->toBeInstanceOf(Title::class);
    expect($result->name)->toBe('Example Title');
    expect($result->type)->toBe(TitleType::Singles);
    expect($result->activations)->toHaveCount(1);
    expect($result->activations->first()->started_at->format('Y-m-d H:i:s'))->toBe($datetime->format('Y-m-d H:i:s'));
});
