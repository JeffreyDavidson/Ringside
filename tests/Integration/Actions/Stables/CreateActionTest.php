<?php

declare(strict_types=1);

use App\Actions\Stables\CreateAction;
use App\Data\Stables\StableData;
use App\Data\Stables\StableMembershipData;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Enums\Stables\StableStatus;
use App\Exceptions\Roster\Stables\CannotBeCreatedException;
use App\Exceptions\Roster\Stables\CannotBeEstablishedException;
use App\Lifecycle\Roster\Stables\StableNameLock;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

test('it creates an unformed stable without a start date', function (): void {
    $data = new StableData(
        name: '  The Alliance  ',
        start_date: null,
        members: new StableMembershipData,
    );

    $stable = resolve(CreateAction::class)->handle($data);

    expect($stable->name)->toBe('The Alliance')
        ->and($stable->status)->toBe(StableStatus::Unformed)
        ->and($stable->activityPeriods()->exists())->toBeFalse()
        ->and($stable->wrestlers()->exists())->toBeFalse();
});

test('it creates and establishes a stable with founding members', function (): void {
    $wrestlers = Wrestler::factory()->employed()->count(3)->create();
    $startDate = now()->subMonth();

    $data = new StableData(
        name: 'The Alliance',
        start_date: $startDate,
        members: new StableMembershipData(wrestlers: $wrestlers),
    );

    $stable = resolve(CreateAction::class)->handle($data);
    $stable->refresh();

    expect($stable->status)->toBe(StableStatus::Active)
        ->and($stable->wrestlers()->count())->toBe(3)
        ->and($stable->currentActivityPeriod()->exists())->toBeTrue()
        ->and($stable->lifecycleTransitions()->sole()->transition)->toBe(LifecycleTransitionType::Established);

    $membership = $stable->wrestlers()->firstOrFail()->pivot;
    $activityPeriod = $stable->activityPeriods()->firstOrFail();

    expect(requiredDate($membership->joined_at)->toDateTimeString())->toBe($startDate->toDateTimeString())
        ->and(requiredDate($activityPeriod->started_at)->toDateTimeString())->toBe($startDate->toDateTimeString());
});

test('it rejects an end date instead of creating an ended stable with current members', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->employed()->count(3)->create();
    $startDate = now()->subMonth();
    $data = new StableData(
        name: 'The Alliance',
        start_date: $startDate,
        members: new StableMembershipData(wrestlers: $wrestlers),
        end_date: $startDate->copy()->addDays(10),
    );

    // Act
    $create = fn () => resolve(CreateAction::class)->handle($data);

    // Assert
    expect($create)->toThrow(CannotBeEstablishedException::class)
        ->and(Stable::query()->where('name', 'The Alliance')->exists())->toBeFalse()
        ->and($wrestlers->firstOrFail()->stables()->exists())->toBeFalse();
});

test('it locks the name of a stable without a promotion before inserting it', function () {
    // Arrange
    $data = new StableData(name: '  The Alliance  ', start_date: null, members: new StableMembershipData);
    $nameKey = resolve(StableNameLock::class)->key('The Alliance');

    // Act
    $statements = recordStatements(fn () => resolve(CreateAction::class)->handle($data));

    // Assert
    $lockPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "stable_name_locks"'));
    $insertPosition = statementPosition($statements, fn (array $statement): bool => str_starts_with($statement['sql'], 'insert into "stables"'));

    expect($statements[$lockPosition]['bindings'])->toBe([$nameKey])
        ->and($lockPosition)->toBeLessThan($insertPosition);
});

test('it takes no name lock for a stable of a promotion', function () {
    // Arrange
    $data = new StableData(name: 'The Alliance', start_date: null, members: new StableMembershipData);

    // Act
    resolve(CreateAction::class)->handle($data, Promotion::factory()->create()->id);

    // Assert
    expect(DB::table('stable_name_locks')->exists())->toBeFalse();
});

test('it rejects a name another active stable without a promotion already uses', function () {
    // Arrange
    Stable::factory()->create(['name' => 'The Alliance']);
    $data = new StableData(name: ' The Alliance ', start_date: null, members: new StableMembershipData);

    // Act
    $create = fn () => resolve(CreateAction::class)->handle($data);

    // Assert
    expect($create)->toThrow(CannotBeCreatedException::class, "an active stable named 'The Alliance' already exists")
        ->and(Stable::query()->where('name', 'The Alliance')->count())->toBe(1);
});

test('it rejects a name another active stable of the promotion already uses', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Alliance']);
    $data = new StableData(name: 'The Alliance', start_date: null, members: new StableMembershipData);

    // Act
    $create = fn () => resolve(CreateAction::class)->handle($data, $promotion->id);

    // Assert
    expect($create)->toThrow(CannotBeCreatedException::class);
});

test('it allows a name only a deleted stable or another promotion uses', function () {
    // Arrange
    Stable::factory()->create(['name' => 'The Alliance'])->delete();
    Stable::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'The Alliance']);
    $data = new StableData(name: 'The Alliance', start_date: null, members: new StableMembershipData);

    // Act
    $stable = resolve(CreateAction::class)->handle($data);

    // Assert
    expect($stable->name)->toBe('The Alliance')
        ->and($stable->promotion_id)->toBeNull();
});

test('it reports a name taken when a concurrent create wins the promotion name between the check and the insert', function () {
    // Arrange
    $promotion = Promotion::factory()->create();
    $data = new StableData(name: 'The Alliance', start_date: null, members: new StableMembershipData);
    Stable::creating(function () use ($promotion): void {
        Stable::withoutEvents(fn () => Stable::factory()->for($promotion, 'promotion')->create(['name' => 'The Alliance']));
    });

    // Act
    $create = fn () => resolve(CreateAction::class)->handle($data, $promotion->id);

    // Assert
    expect($create)->toThrow(CannotBeCreatedException::class, "an active stable named 'The Alliance' already exists");
});
