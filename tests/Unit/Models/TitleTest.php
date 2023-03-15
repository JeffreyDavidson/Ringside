<?php

use App\Builders\TitleQueryBuilder;
use App\Enums\TitleStatus;
use App\Models\Concerns\HasActivations;
use App\Models\Concerns\HasChampionships;
use App\Models\Concerns\HasRetirements;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a title has a name', function () {
    $title = Title::factory()->create(['name' => 'Example Name Title']);

    expect($title)->name->toBe('Example Name Title');
});

test('a title has a status', function () {
    $title = Title::factory()->create();

    expect($title)->status->toBeInstanceOf(TitleStatus::class);
});

test('a title is unactivated by default', function () {
    $title = Title::factory()->create();

    expect($title)->status->toMatchObject(TitleStatus::UNACTIVATED);
});

test('a title uses has activation trait', function () {
    expect(Title::class)->usesTrait(HasActivations::class);
});

test('a title uses has championships trait', function () {
    expect(Title::class)->usesTrait(HasChampionships::class);
});

test('a title uses has retirements trait', function () {
    expect(Title::class)->usesTrait(HasRetirements::class);
});

test('a title uses has factory trait', function () {
    expect(Title::class)->usesTrait(HasFactory::class);
});

test('a title uses soft deleted trait', function () {
    expect(Title::class)->usesTrait(SoftDeletes::class);
});

test('a title has its own eloquent builder', function () {
    expect(new Title())->query()->toBeInstanceOf(TitleQueryBuilder::class);
});
