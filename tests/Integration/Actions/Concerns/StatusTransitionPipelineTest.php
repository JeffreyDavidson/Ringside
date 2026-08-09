<?php

declare(strict_types=1);

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
});

test('it creates an employment period on the effective date', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();
    $effectiveDate = now()->subDays(10);

    StatusTransitionPipeline::employ($wrestler, $effectiveDate)
        ->execute();

    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it creates a suspension period on the effective date', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subDays(3);

    StatusTransitionPipeline::suspend($wrestler, $effectiveDate)
        ->execute();

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it ends active employment and suspension periods when released', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $wrestler->suspensions()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);
    $effectiveDate = now()->subDay();

    StatusTransitionPipeline::release($wrestler, $effectiveDate)
        ->execute();

    expect($wrestler->employments()->whereNull('ended_at')->exists())
        ->toBeFalse()
        ->and($wrestler->suspensions()->whereNull('ended_at')->exists())
        ->toBeFalse();

    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
});

test('it ends active employment and injury periods when released', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $effectiveDate = now()->subDay();

    StatusTransitionPipeline::release($wrestler, $effectiveDate)
        ->execute();

    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
});

test('it ends employment and creates a retirement period atomically', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    $effectiveDate = now()->subDay();

    StatusTransitionPipeline::retire($wrestler, $effectiveDate)
        ->execute();

    $this->assertDatabaseHas('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'started_at' => $effectiveDate->toDateTimeString(),
        'ended_at' => null,
    ]);
});

test('it ends an active suspension period when reinstated', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $effectiveDate = now()->subDay();

    StatusTransitionPipeline::reinstate($wrestler, $effectiveDate)
        ->execute();

    $this->assertDatabaseHas('wrestlers_suspensions', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
});

test('it ends an active injury period when reinstated', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $effectiveDate = now()->subDay();

    StatusTransitionPipeline::reinstate($wrestler, $effectiveDate)
        ->execute();

    $this->assertDatabaseHas('wrestlers_injuries', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
});

test('it ends an active retirement period when unretired', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $effectiveDate = now()->subDay();

    StatusTransitionPipeline::unretire($wrestler, $effectiveDate)
        ->execute();

    $this->assertDatabaseHas('wrestlers_retirements', [
        'wrestler_id' => $wrestler->id,
        'ended_at' => $effectiveDate->toDateTimeString(),
    ]);
});

test('it rolls back the primary transition when a cascade fails', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();

    $transition = StatusTransitionPipeline::employ($wrestler)
        ->withCascade(function (Model $entity, Carbon $date, string $name): void {
            throw new RuntimeException('Cascade failed.');
        });

    expect(fn () => $transition->execute())
        ->toThrow(RuntimeException::class, 'Cascade failed.');

    $this->assertDatabaseMissing('wrestlers_employments', [
        'wrestler_id' => $wrestler->id,
    ]);
});
