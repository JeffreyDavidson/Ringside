<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousTitleChampionships;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->create();
    actingAs(administrator());
});

it('renders title championship history for administrators', function () {
    livewire(PreviousTitleChampionships::class, ['tagTeamId' => $this->tagTeam->id])
        ->assertSuccessful();
});

it('forbids users without access to the tag team', function (string $actor) {
    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    livewire(PreviousTitleChampionships::class, ['tagTeamId' => $this->tagTeam->id])
        ->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
