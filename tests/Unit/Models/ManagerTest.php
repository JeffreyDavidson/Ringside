<?php

declare(strict_types=1);

use App\Builders\ManagerBuilder;
use App\Enums\ManagerStatus;
use App\Models\Concerns\CanJoinStables;
use App\Models\Concerns\Manageables;
use App\Models\Concerns\OwnedByUser;
use App\Models\Contracts\CanBeAStableMember;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

test('a manager is unemployed by default', function () {
    $manager = Manager::factory()->create();

    expect($manager->status->value)->toBe(ManagerStatus::Unemployed->value);
});

test('a manager implements can be stable manager interface', function () {
    expect(class_implements(Manager::class))->toContain(CanBeAStableMember::class);
});

test('a manager uses can join stables trait', function () {
    expect(Manager::class)->usesTrait(CanJoinStables::class);
});

test('a manager implements manageable interface', function () {
    expect(Manager::class)->usesTrait(Manageables::class);
});

test('a manager uses owned by user trait', function () {
    expect(Manager::class)->usesTrait(OwnedByUser::class);
});

test('a manager uses has factory trait', function () {
    expect(Manager::class)->usesTrait(HasFactory::class);
});

test('a manager uses soft deleted trait', function () {
    expect(Manager::class)->usesTrait(SoftDeletes::class);
});

test('a manager has its own eloquent builder', function () {

    expect(new Manager())->query()->toBeInstanceOf(ManagerBuilder::class);
});

test('a manager has a display name', function () {
    $manager = Manager::factory()->create(['first_name' => 'Hulk', 'last_name' => 'Hogan']);

    expect($manager)->getIdentifier()->toBe('Hulk Hogan');
});
