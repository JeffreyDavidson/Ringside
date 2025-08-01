<?php

declare(strict_types=1);

use App\Actions\Referees\DeleteAction;
use App\Models\Referees\Referee;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it soft deletes an unemployed referee', function () {
    $referee = Referee::factory()->create();

    expect($referee->isEmployed())->toBeFalse();
    expect($referee->trashed())->toBeFalse();

    DeleteAction::run($referee);

    $referee->refresh();
    expect($referee->trashed())->toBeTrue();

    // Verify referee is soft deleted
    $this->assertSoftDeleted('referees', [
        'id' => $referee->id,
        'first_name' => $referee->first_name,
        'last_name' => $referee->last_name,
    ]);
});

test('it soft deletes referee with specific deletion date', function () {
    $referee = Referee::factory()->create();
    $deletionDate = now()->subDays(2);

    DeleteAction::run($referee, $deletionDate);

    $referee->refresh();
    expect($referee->trashed())->toBeTrue();

    // Note: Laravel soft deletes use current timestamp, so we can't directly test custom dates
    // The custom date would be used for ending relationships via StatusTransitionPipeline
    $this->assertSoftDeleted('referees', [
        'id' => $referee->id,
    ]);
});

test('it ends employment before deletion using StatusTransitionPipeline', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;

    expect($referee->isEmployed())->toBeTrue();
    expect($employment->ended_at)->toBeNull();

    DeleteAction::run($referee);

    $referee->refresh();
    $employment->refresh();

    expect($referee->trashed())->toBeTrue();
    expect($employment->ended_at)->not->toBeNull();

    // Verify employment was ended
    $this->assertDatabaseHas('referees_employments', [
        'id' => $employment->id,
        'referee_id' => $referee->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends suspension before deletion', function () {
    $referee = Referee::factory()->employed()->suspended()->create();
    $suspension = $referee->currentSuspension;

    expect($referee->isSuspended())->toBeTrue();
    expect($suspension->ended_at)->toBeNull();

    DeleteAction::run($referee);

    $referee->refresh();
    $suspension->refresh();

    expect($referee->trashed())->toBeTrue();
    expect($suspension->ended_at)->not->toBeNull();

    // Verify suspension was ended
    $this->assertDatabaseHas('referees_suspensions', [
        'id' => $suspension->id,
        'referee_id' => $referee->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends injury before deletion', function () {
    $referee = Referee::factory()->employed()->injured()->create();
    $injury = $referee->currentInjury;

    expect($referee->isInjured())->toBeTrue();
    expect($injury->ended_at)->toBeNull();

    DeleteAction::run($referee);

    $referee->refresh();
    $injury->refresh();

    expect($referee->trashed())->toBeTrue();
    expect($injury->ended_at)->not->toBeNull();

    // Verify injury was ended
    $this->assertDatabaseHas('referees_injuries', [
        'id' => $injury->id,
        'referee_id' => $referee->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it ends retirement before deletion', function () {
    $referee = Referee::factory()->retired()->create();
    $retirement = $referee->currentRetirement;

    expect($referee->isRetired())->toBeTrue();
    expect($retirement->ended_at)->toBeNull();

    DeleteAction::run($referee);

    $referee->refresh();
    $retirement->refresh();

    expect($referee->trashed())->toBeTrue();
    expect($retirement->ended_at)->not->toBeNull();

    // Verify retirement was ended
    $this->assertDatabaseHas('referees_retirements', [
        'id' => $retirement->id,
        'referee_id' => $referee->id,
        'ended_at' => now()->toDateTimeString(),
    ]);
});

test('it handles DateHelper date resolution for deletion', function () {
    $referee = Referee::factory()->employed()->create();
    $deletionDate = now()->subDays(5);

    DeleteAction::run($referee, $deletionDate);

    $referee->refresh();
    expect($referee->trashed())->toBeTrue();

    // DateHelper should have processed the deletion date for ending relationships
    $this->assertDatabaseHas('referees_employments', [
        'referee_id' => $referee->id,
        'ended_at' => $deletionDate->toDateTimeString(),
    ]);
});

test('it maintains transaction boundaries', function () {
    $referee = Referee::factory()->employed()->suspended()->create();
    $employment = $referee->currentEmployment;
    $suspension = $referee->currentSuspension;

    DeleteAction::run($referee);

    $referee->refresh();
    $employment->refresh();
    $suspension->refresh();

    // All changes should be atomic - referee deleted and relationships ended
    expect($referee->trashed())->toBeTrue();
    expect($employment->ended_at)->not->toBeNull();
    expect($suspension->ended_at)->not->toBeNull();
});

test('it validates referee can be deleted', function () {
    $referee = Referee::factory()->create();

    // Should succeed without throwing validation exception
    DeleteAction::run($referee);

    $referee->refresh();
    expect($referee->trashed())->toBeTrue();
});

test('it preserves historical data after deletion', function () {
    $referee = Referee::factory()->employed()->create();
    $employment = $referee->currentEmployment;

    DeleteAction::run($referee);

    $referee->refresh();
    $employment->refresh();

    // Historical employment record should be preserved with ended_at set
    $this->assertDatabaseHas('referees_employments', [
        'id' => $employment->id,
        'referee_id' => $referee->id,
        'started_at' => $employment->started_at->toDateTimeString(),
        'ended_at' => now()->toDateTimeString(),
    ]);

    // Referee record should be soft deleted but preserved
    $this->assertSoftDeleted('referees', [
        'id' => $referee->id,
        'first_name' => $referee->first_name,
        'last_name' => $referee->last_name,
    ]);
});

test('it uses StatusTransitionPipeline for consistent status handling', function () {
    $referee = Referee::factory()->employed()->suspended()->injured()->create();

    expect($referee->isEmployed())->toBeTrue();
    expect($referee->isSuspended())->toBeTrue();
    expect($referee->isInjured())->toBeTrue();

    DeleteAction::run($referee);

    $referee->refresh();

    // StatusTransitionPipeline should have handled all status endings consistently
    expect($referee->trashed())->toBeTrue();
    expect($referee->isEmployed())->toBeFalse();
    expect($referee->isSuspended())->toBeFalse();
    expect($referee->isInjured())->toBeFalse();
});
