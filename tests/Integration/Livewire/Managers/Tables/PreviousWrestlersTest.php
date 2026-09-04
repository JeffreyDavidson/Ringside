<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousWrestlers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());
});

it('renders previous wrestlers without lazy loading each relationship', function () {
    $manager = Manager::factory()->create();
    $wrestler = Wrestler::factory()->create();
    WrestlerManager::query()->create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $manager->id,
        'hired_at' => now()->subYear(),
        'fired_at' => now()->subMonth(),
    ]);
    $table = new PreviousWrestlers();
    $table->managerId = $manager->id;
    $assignment = $table->builder()->firstOrFail();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $renderedWrestler = $table->columns()[0]->resolveValue($assignment);

    expect($renderedWrestler)->toBe($wrestler->name)
        ->and($assignment->relationLoaded('wrestler'))->toBeTrue()
        ->and(DB::getQueryLog())->toBeEmpty();
});

it('forbids users without access to the manager', function (string $actor) {
    $manager = Manager::factory()->create();

    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    livewire(PreviousWrestlers::class, ['managerId' => $manager->id])
        ->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
