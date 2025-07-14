<?php

declare(strict_types=1);

use App\Builders\Titles\TitleBuilder;
use App\Enums\Shared\ActivationStatus;
use App\Models\Concerns\HasChampionships;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a title has a name', function () {
    $title = Title::factory()->create(['name' => 'Example Name Title']);

    expect($title)->name->toBe('Example Name Title');
});

test('a title has a status', function () {
    $title = Title::factory()->create();

    expect($title)->status->toBeInstanceOf(ActivationStatus::class);
});

test('a title is unactivated by default', function () {
    $title = Title::factory()->create();

    expect($title->status->value)->toBe(ActivationStatus::Unactivated->value);
});

test('a title uses has championships trait', function () {
    expect(Title::class)->usesTrait(HasChampionships::class);
});

test('a title uses has factory trait', function () {
    expect(Title::class)->usesTrait(HasFactory::class);
});

test('a title uses soft deleted trait', function () {
    expect(Title::class)->usesTrait(SoftDeletes::class);
});

test('a title has its own eloquent builder', function () {
    expect(new Title())->query()->toBeInstanceOf(TitleBuilder::class);
});
