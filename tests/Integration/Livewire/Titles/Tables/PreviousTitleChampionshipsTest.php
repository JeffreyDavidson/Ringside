<?php

declare(strict_types=1);

use App\Livewire\Support\RosterResourceRouteResolver;
use App\Livewire\Titles\Tables\PreviousTitleChampionships;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('displays championship reign length from its dates', function () {
    $championship = new TitleChampionship([
        'won_at' => '2025-01-01',
        'lost_at' => '2025-01-11',
    ]);

    $table = new PreviousTitleChampionships();
    $table->boot(app(RosterResourceRouteResolver::class));

    $reignLengthColumn = $table->columns()[3];

    expect($reignLengthColumn->resolveValue($championship))->toBe('10');
});

test('only retrieves previous championships for the selected title', function () {
    $title = Title::factory()->create();
    $olderChampionship = TitleChampionship::factory()->for($title)->ended()->create([
        'won_at' => now()->subYears(4),
        'lost_at' => now()->subYears(3),
    ]);
    $latestChampionship = TitleChampionship::factory()->for($title)->ended()->create([
        'won_at' => now()->subYears(2),
        'lost_at' => now()->subYear(),
    ]);
    TitleChampionship::factory()->for($title)->current()->create();
    TitleChampionship::factory()->ended()->create();
    $table = new PreviousTitleChampionships();
    $table->titleId = $title->id;

    $championships = $table->builder()->get();

    expect($championships->modelKeys())->toBe([
        $latestChampionship->id,
        $olderChampionship->id,
    ]);
});

test('renders the title championship history from reign relationships and dates', function () {
    $title = Title::factory()->create();
    $previousChampion = Wrestler::factory()->create(['name' => 'First Champion']);
    $newChampion = TagTeam::factory()->create(['name' => 'New Champions']);
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler($previousChampion)
        ->wonOn('2024-01-01')
        ->lostOn('2024-06-01')
        ->create();
    TitleChampionship::factory()
        ->for($title)
        ->forTagTeam($newChampion)
        ->wonOn('2024-06-01')
        ->lostOn('2025-01-01')
        ->create();
    actingAs(administrator());

    livewire(PreviousTitleChampionships::class, ['titleId' => $title->id])
        ->assertSee('First Champion')
        ->assertSee('New Champions')
        ->assertSeeHtml(route('wrestlers.show', $previousChampion))
        ->assertSeeHtml(route('tag-teams.show', $newChampion))
        ->assertSee('2024-06-01 - 2025-01-01');
});

it('authorizes the selected title instance', function () {
    $title = Title::factory()->create();
    $authorizedTitle = null;

    Gate::before(function ($user, string $ability, array $arguments) use (&$authorizedTitle): ?bool {
        if ($ability !== 'view' || ! ($arguments[0] ?? null) instanceof Title) {
            return null;
        }

        $authorizedTitle = $arguments[0];

        return true;
    });
    actingAs(basicUser());

    livewire(PreviousTitleChampionships::class, ['titleId' => $title->id])
        ->assertSuccessful();

    expect($authorizedTitle?->is($title))->toBeTrue();
});

it('forbids users without access to the title', function (string $actor) {
    $title = Title::factory()->create();

    if ($actor === 'guest') {
        Auth::logout();
    } else {
        actingAs(basicUser());
    }

    livewire(PreviousTitleChampionships::class, ['titleId' => $title->id])
        ->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);
