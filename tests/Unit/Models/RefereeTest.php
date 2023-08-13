<?php

use App\Builders\RefereeBuilder;
use App\Builders\RefereeQueryBuilder;
use App\Enums\RefereeStatus;
use App\Models\Referee;
use App\Models\SingleRosterMember;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a referee has a first name', function () {
    $referee = Referee::factory()->create(['first_name' => 'John']);

    expect($referee)->first_name->toBe('John');
});

test('a referee has a last name', function () {
    $referee = Referee::factory()->create(['last_name' => 'Smith']);

    expect($referee)->last_name->toBe('Smith');
});

test('a referee has a status', function () {
    $referee = Referee::factory()->create();

    expect($referee)->status->toBeInstanceOf(RefereeStatus::class);
});

test('a referee is unemployed by default', function () {
    $referee = Referee::factory()->create();

    expect($referee->status->value)->toBe(RefereeStatus::UNEMPLOYED->value);
});

test('a referee uses has factory trait', function () {
    expect(Referee::class)->usesTrait(HasFactory::class);
});

test('a referee uses soft deleted trait', function () {
    expect(Referee::class)->usesTrait(SoftDeletes::class);
});

test('a referee has its own eloquent builder', function () {
    expect(new Referee())->query()->toBeInstanceOf(RefereeBuilder::class);
});

test('a referee has a display name', function () {
    $referee = Referee::factory()->create(['first_name' => 'Hulk', 'last_name' => 'Hogan']);

    expect($referee)->getIdentifier()->toBe('Hulk Hogan');
});
