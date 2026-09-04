<?php

declare(strict_types=1);

use App\Livewire\Referees\Tables\PreviousMatches;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->referee = Referee::factory()->create();
    actingAs(administrator());
});

it('renders match history for administrators', function () {
    $table = livewire(PreviousMatches::class, ['refereeId' => $this->referee->id]);

    $table->assertSuccessful();
});

it('forbids users without access to the referee', function (string $actor) {
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    $table = livewire(PreviousMatches::class, ['refereeId' => $this->referee->id]);

    $table->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
