<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\PreviousTitleChampionships;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->tagTeam = TagTeam::factory()->create();
    actingAs(administrator());
});

it('renders title championship history for administrators', function () {
    livewire(PreviousTitleChampionships::class, ['tagTeamId' => $this->tagTeam->id])
        ->assertSuccessful();
});

it('searches previous championships by title name', function (): void {
    // Arrange
    foreach (['Historic Tag Titles', 'Former Tag Titles'] as $offset => $name) {
        $title = Title::factory()->tagTeam()->create(['name' => $name]);
        TitleChampionship::factory()
            ->for($title)
            ->forTagTeam($this->tagTeam)
            ->wonOn(now()->subMonths($offset + 3)->toDateString())
            ->lostOn(now()->subMonths($offset + 1)->toDateString())
            ->create();
    }

    // Act
    $component = livewire(PreviousTitleChampionships::class, ['tagTeamId' => $this->tagTeam->id]);
    $component->set('search', 'Historic');

    // Assert
    $component
        ->assertSee('Historic Tag Titles')
        ->assertDontSee('Former Tag Titles');
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
