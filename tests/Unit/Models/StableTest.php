<?php

declare(strict_types=1);

use App\Enums\StableStatus;
use App\Models\Concerns\OwnedByUser;
use App\Models\Contracts\Activatable;
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

test('a stable uses owned by user trait', function () {
    expect(Stable::class)->usesTrait(OwnedByUser::class);
});

test('a stable implements activatable interface', function () {
    expect(class_implements(Stable::class))->toContain(Activatable::class);
});

test('a stable implements retirable interface', function () {
    expect(class_implements(Stable::class))->toContain(Retirable::class);
});
