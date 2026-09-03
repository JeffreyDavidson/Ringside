<?php

declare(strict_types=1);

use App\Livewire\Base\Tables\BasePreviousMatchesTable;
use App\Livewire\Referees\Tables\PreviousMatches as RefereePreviousMatches;
use App\Livewire\Table\DataTableComponent;
use App\Livewire\TagTeams\Tables\PreviousMatches as TagTeamPreviousMatches;
use App\Livewire\Wrestlers\Tables\PreviousMatches as WrestlerPreviousMatches;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Model;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('uses the event matches table qualifier when rendering match history', function (
    string $component,
    string $ownerParameter,
    Closure $ownerFactory,
) {
    $owner = $ownerFactory();
    actingAs(User::factory()->administrator()->create());
    $table = app($component);
    if (! $table instanceof BasePreviousMatchesTable) {
        throw new LogicException('Previous match tables must extend the shared base table.');
    }

    $table->mountShowTableTrait();
    $additionalSelects = (new ReflectionProperty(DataTableComponent::class, 'additionalSelects'))
        ->getValue($table);

    expect($additionalSelects)->toContain('events_matches.id as id');

    livewire($component, [$ownerParameter => $owner->getKey()])
        ->assertSuccessful();
})->with([
    'wrestler history' => [
        WrestlerPreviousMatches::class,
        'wrestlerId',
        static fn (): Model => Wrestler::factory()->create(),
    ],
    'tag team history' => [
        TagTeamPreviousMatches::class,
        'tagTeamId',
        static fn (): Model => TagTeam::factory()->create(),
    ],
    'referee history' => [
        RefereePreviousMatches::class,
        'refereeId',
        static fn (): Model => Referee::factory()->create(),
    ],
]);
