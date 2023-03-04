<?php

use App\Enums\TitleStatus;
use App\Models\Concerns\Activations;
use App\Models\Concerns\Competable;
use App\Models\Concerns\HasRetirements;
use App\Models\Title;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a title has a name', function () {
    $title = Title::factory()->create(['name' => 'Example Name Title']);

    expect($title)->name->toBe('Example Name Title');
});

test('a title has a status', function () {
    $title = Title::factory()->create();

    expect($title)->status->toBeInstanceOf(TitleStatus::class);
});

test('a title uses soft deleted trait', function () {
    expect(Title::class)->usesTrait(SoftDeletes::class);
});

test('a title uses activation trait', function () {
    expect(Title::class)->usesTrait(Activations::class);
});

test('a title uses competable trait', function () {
    expect(Title::class)->usesTrait(Competable::class);
});

test('a title uses retirements trait', function () {
    expect(Title::class)->usesTrait(HasRetirements::class);
});
