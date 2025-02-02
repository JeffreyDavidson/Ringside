<?php

declare(strict_types=1);

use App\Data\TitleData;
use App\Models\Title;
use App\Models\TitleActivation;
use App\Repositories\TitleRepository;

test('it creates a title', function () {
    $data = new TitleData('Example Name Title', null);

    $title = app(TitleRepository::class)->create($data);

    expect($title)
        ->name->toEqual('Example Name Title');
});

test('it updates a title', function () {
    $title = Title::factory()->create();
    $data = new TitleData('Example Name Title', null);

    $title = app(TitleRepository::class)->update($title, $data);

    expect($title)
        ->name->toEqual('Example Name Title');
});

test('it deletes a title', function () {
    $title = Title::factory()->create();

    app(TitleRepository::class)->delete($title);

    expect($title)
        ->deleted_at->not()->toBeNull();
});

test('it restores a trashed title', function () {
    $title = Title::factory()->trashed()->create();

    app(TitleRepository::class)->restore($title);

    expect($title->fresh())
        ->deleted_at->toBeNull();
});

test('it activates a title', function () {
    $title = Title::factory()->create();
    $datetime = now();

    $title = app(TitleRepository::class)->activate($title, $datetime);

    expect($title->fresh())->activations->toHaveCount(1);
    expect($title->fresh()->activations->first())->started_at->eq($datetime);
});

test('it updates an activation of a title', function () {
    $datetime = now();
    $title = Title::factory()
        ->has(TitleActivation::factory()->started($datetime->copy()->addDays(2)), 'activations')
        ->create();

    expect($title->fresh())->activations->toHaveCount(1);
    expect($title->fresh()->activations->first())
        ->started_at->toDateTimeString()->toEqual($datetime->copy()->addDays(2)->toDateTimeString());

    $title = app(TitleRepository::class)->activate($title, $datetime);

    expect($title->fresh())->activations->toHaveCount(1);
    expect($title->fresh()->activations->first())->started_at->eq($datetime);
});

test('it deactivates a title', function () {
    $title = Title::factory()->active()->create();
    $datetime = now();

    $title = app(TitleRepository::class)->deactivate($title, $datetime);

    expect($title->fresh())->activations->toHaveCount(1);
    expect($title->fresh()->activations->first())->ended_at->eq($datetime);
});

test('it retire a title', function () {
    $title = Title::factory()->active()->create();
    $datetime = now();

    $title = app(TitleRepository::class)->retire($title, $datetime);

    expect($title->fresh())->retirements->toHaveCount(1);
    expect($title->fresh()->retirements->first())->started_at->eq($datetime);
});

test('it unretire a title', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now();

    $title = app(TitleRepository::class)->unretire($title, $datetime);

    expect($title->fresh())->retirements->toHaveCount(1);
    expect($title->fresh()->retirements->first())->ended_at->eq($datetime);
});
