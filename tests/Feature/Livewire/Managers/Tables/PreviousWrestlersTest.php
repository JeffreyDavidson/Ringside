<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\PreviousWrestlers;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::factory()->administrator()->create());
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
