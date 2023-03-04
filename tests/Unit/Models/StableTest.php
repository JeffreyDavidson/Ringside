<?php

use App\Enums\StableStatus;
use App\Models\Concerns\Activations;
use App\Models\Concerns\Deactivations;
use App\Models\Concerns\OwnedByUser;
use App\Models\Contracts\Activatable;
use App\Models\Contracts\Deactivatable;
use App\Models\Contracts\Retirable;
use App\Models\Stable;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a stable has a name', function () {
    $stable = Stable::factory()->create(['name' => 'Example Stable Name']);

    expect($stable)->name->toBe('Example Stable Name');
});

test('a stable has a status', function () {
    $stable = Stable::factory()->create();

    expect($stable)->status->toBeInstanceOf(StableStatus::class);
});

test('a stable uses soft deleted trait', function () {
    expect(Stable::class)->usesTrait(SoftDeletes::class);
});

test('a stable uses activations trait', function () {
    expect(Stable::class)->usesTrait(Activations::class);
});

test('a stable uses deactivations trait', function () {
    expect(Stable::class)->usesTrait(Deactivations::class);
});

test('a stable uses owned by user trait', function () {
    expect(Stable::class)->usesTrait(OwnedByUser::class);
});

test('a stable implements activatable interface', function () {
    expect(class_implements(Stable::class))->toContain(Activatable::class);
});

test('a stable implements deactivatable interface', function () {
    expect(class_implements(Stable::class))->toContain(Deactivatable::class);
});

test('a stable implements retirable interface', function () {
    expect(class_implements(Stable::class))->toContain(Retirable::class);
});
