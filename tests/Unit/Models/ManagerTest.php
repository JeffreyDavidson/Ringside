<?php

use App\Enums\ManagerStatus;
use App\Models\Concerns\HasFullName;
use App\Models\Manager;
use App\Models\SingleRosterMember;
use Illuminate\Database\Eloquent\SoftDeletes;

test('a manager has a first name', function () {
    $manager = Manager::factory()->create(['first_name' => 'John']);

    expect($manager)->first_name->toBe('John');
});

test('a manager has a last name', function () {
    $manager = Manager::factory()->create(['last_name' => 'Smith']);

    expect($manager)->last_name->toBe('Smith');
});

test('a manager has a status', function () {
    $manager = Manager::factory()->create();

    expect($manager)->status->toBeInstanceOf(ManagerStatus::class);
});

test('a manager is a single roster member', function () {
    expect(get_parent_class(Manager::class))->toBe(SingleRosterMember::class);
});

test('a manager uses soft deleted trait', function () {
    expect(Manager::class)->usesTrait(SoftDeletes::class);
});

test('a manager uses has full name trait', function () {
    expect(Manager::class)->usesTrait(HasFullName::class);
});
