<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousMatches;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->create();
    actingAs(administrator());
});

it('renders match history for administrators', function () {
    $table = livewire(PreviousMatches::class, ['tagTeamId' => $this->tagTeam->id]);

    $table->assertSuccessful();
});

it('forbids users without access to the tag team', function (string $actor) {
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    $table = livewire(PreviousMatches::class, ['tagTeamId' => $this->tagTeam->id]);

    $table->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
