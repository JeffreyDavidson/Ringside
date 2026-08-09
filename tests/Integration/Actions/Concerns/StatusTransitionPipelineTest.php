<?php

declare(strict_types=1);

use App\Actions\Concerns\StatusTransitionPipeline;
use App\Models\Wrestlers\Wrestler;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();
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
