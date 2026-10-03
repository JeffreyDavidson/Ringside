<?php

declare(strict_types=1);

use App\Livewire\Managers\Components\Actions as ManagerActions;
use App\Livewire\Referees\Components\Actions as RefereeActions;
use App\Livewire\Stables\Components\Actions as StableActions;
use App\Livewire\TagTeams\Components\Actions as TagTeamActions;
use App\Livewire\Titles\Components\Actions as TitleActions;
use App\Livewire\Wrestlers\Components\Actions as WrestlerActions;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Database\Eloquent\Model;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * @return array<string, Element> The rendered lifecycle buttons keyed by the Livewire method they call.
 */
function renderedLifecycleButtons(string $component, string $property, Model $model): array
{
    $html = livewire($component, [$property => $model])->html();

    $buttons = [];

    foreach (HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('button') as $button) {
        $buttons[(string) $button->getAttribute('wire:click')] = $button;
    }

    return $buttons;
}

describe('lifecycle action buttons', function (): void {
    test('it asks for confirmation naming the record before a destructive action', function (
        string $component,
        string $property,
        Closure $makeModel,
        string $method,
        string $message,
    ): void {
        // Arrange
        actingAs(administrator());
        $model = $makeModel();

        // Act
        $buttons = renderedLifecycleButtons($component, $property, $model);

        // Assert
        expect($buttons)->toHaveKey($method)
            ->and($buttons[$method]->getAttribute('wire:confirm'))->toBe($message);
    })->with([
        'wrestler release' => [WrestlerActions::class, 'wrestler', fn (): Model => Wrestler::factory()->bookable()->create(['name' => "Ann D'Arcy"]), 'release', "Release Ann D'Arcy?"],
        'wrestler suspend' => [WrestlerActions::class, 'wrestler', fn (): Model => Wrestler::factory()->bookable()->create(['name' => "Ann D'Arcy"]), 'suspend', "Suspend Ann D'Arcy?"],
        'wrestler injure' => [WrestlerActions::class, 'wrestler', fn (): Model => Wrestler::factory()->bookable()->create(['name' => "Ann D'Arcy"]), 'injure', "Mark Ann D'Arcy as injured?"],
        'wrestler retire' => [WrestlerActions::class, 'wrestler', fn (): Model => Wrestler::factory()->bookable()->create(['name' => "Ann D'Arcy"]), 'retire', "Retire Ann D'Arcy?"],
        'manager release' => [ManagerActions::class, 'manager', fn (): Model => Manager::factory()->available()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy"])->refresh(), 'release', "Release Ann D'Arcy?"],
        'manager suspend' => [ManagerActions::class, 'manager', fn (): Model => Manager::factory()->available()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy"])->refresh(), 'suspend', "Suspend Ann D'Arcy?"],
        'manager injure' => [ManagerActions::class, 'manager', fn (): Model => Manager::factory()->available()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy"])->refresh(), 'injure', "Mark Ann D'Arcy as injured?"],
        'manager retire' => [ManagerActions::class, 'manager', fn (): Model => Manager::factory()->available()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy"])->refresh(), 'retire', "Retire Ann D'Arcy?"],
        'referee release' => [RefereeActions::class, 'referee', fn (): Model => Referee::factory()->bookable()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy"])->refresh(), 'release', "Release Ann D'Arcy?"],
        'referee suspend' => [RefereeActions::class, 'referee', fn (): Model => Referee::factory()->bookable()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy"])->refresh(), 'suspend', "Suspend Ann D'Arcy?"],
        'referee injure' => [RefereeActions::class, 'referee', fn (): Model => Referee::factory()->bookable()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy"])->refresh(), 'injure', "Mark Ann D'Arcy as injured?"],
        'referee retire' => [RefereeActions::class, 'referee', fn (): Model => Referee::factory()->bookable()->create(['first_name' => 'Ann', 'last_name' => "D'Arcy"])->refresh(), 'retire', "Retire Ann D'Arcy?"],
        'tag team release' => [TagTeamActions::class, 'tagTeam', fn (): Model => TagTeam::factory()->bookable()->create(['name' => "The D'Arcys"]), 'release', "Release The D'Arcys?"],
        'tag team suspend' => [TagTeamActions::class, 'tagTeam', fn (): Model => TagTeam::factory()->bookable()->create(['name' => "The D'Arcys"]), 'suspend', "Suspend The D'Arcys?"],
        'tag team retire' => [TagTeamActions::class, 'tagTeam', fn (): Model => TagTeam::factory()->bookable()->create(['name' => "The D'Arcys"]), 'retire', "Retire The D'Arcys?"],
        'stable disband' => [StableActions::class, 'stable', fn (): Model => Stable::factory()->active()->create(['name' => "D'Arcy & Co"]), 'disband', "Disband D'Arcy & Co?"],
        'stable retire' => [StableActions::class, 'stable', fn (): Model => Stable::factory()->active()->create(['name' => "D'Arcy & Co"]), 'retire', "Retire D'Arcy & Co?"],
        'title retire' => [TitleActions::class, 'title', fn (): Model => Title::factory()->active()->create(['name' => "D'Arcy Title"]), 'retire', "Retire D'Arcy Title?"],
        'title deactivate' => [TitleActions::class, 'title', fn (): Model => Title::factory()->active()->create(['name' => "D'Arcy Title"]), 'deactivate', "Deactivate D'Arcy Title?"],
    ]);

    test('it disables each lifecycle button while its own action runs and only confirms destructive actions', function (
        string $component,
        string $property,
        Closure $makeModel,
    ): void {
        // Arrange
        actingAs(administrator());
        $model = $makeModel();
        $destructive = ['release', 'suspend', 'injure', 'retire', 'disband', 'deactivate'];

        // Act
        $buttons = renderedLifecycleButtons($component, $property, $model);

        // Assert
        expect($buttons)->not->toBeEmpty();

        foreach ($buttons as $method => $button) {
            expect($button->getAttribute('wire:loading.attr'))->toBe('disabled')
                ->and($button->getAttribute('wire:target'))->toBe($method)
                ->and($button->hasAttribute('wire:confirm'))->toBe(in_array($method, $destructive, true));
        }
    })->with([
        'unemployed wrestler' => [WrestlerActions::class, 'wrestler', fn (): Model => Wrestler::factory()->unemployed()->create()],
        'bookable wrestler' => [WrestlerActions::class, 'wrestler', fn (): Model => Wrestler::factory()->bookable()->create()],
        'suspended wrestler' => [WrestlerActions::class, 'wrestler', fn (): Model => Wrestler::factory()->suspended()->create()],
        'injured wrestler' => [WrestlerActions::class, 'wrestler', fn (): Model => Wrestler::factory()->injured()->create()],
        'retired wrestler' => [WrestlerActions::class, 'wrestler', fn (): Model => Wrestler::factory()->retired()->create()],
        'deleted wrestler' => [WrestlerActions::class, 'wrestler', fn (): Model => Wrestler::factory()->trashed()->create()],
        'unemployed manager' => [ManagerActions::class, 'manager', fn (): Model => Manager::factory()->unemployed()->create()],
        'available manager' => [ManagerActions::class, 'manager', fn (): Model => Manager::factory()->available()->create()],
        'suspended manager' => [ManagerActions::class, 'manager', fn (): Model => Manager::factory()->suspended()->create()],
        'injured manager' => [ManagerActions::class, 'manager', fn (): Model => Manager::factory()->injured()->create()],
        'retired manager' => [ManagerActions::class, 'manager', fn (): Model => Manager::factory()->retired()->create()],
        'unemployed referee' => [RefereeActions::class, 'referee', fn (): Model => Referee::factory()->unemployed()->create()],
        'bookable referee' => [RefereeActions::class, 'referee', fn (): Model => Referee::factory()->bookable()->create()],
        'suspended referee' => [RefereeActions::class, 'referee', fn (): Model => Referee::factory()->suspended()->create()],
        'injured referee' => [RefereeActions::class, 'referee', fn (): Model => Referee::factory()->injured()->create()],
        'retired referee' => [RefereeActions::class, 'referee', fn (): Model => Referee::factory()->retired()->create()],
        'unemployed tag team' => [TagTeamActions::class, 'tagTeam', fn (): Model => TagTeam::factory()->unemployed()->create()],
        'bookable tag team' => [TagTeamActions::class, 'tagTeam', fn (): Model => TagTeam::factory()->bookable()->create()],
        'suspended tag team' => [TagTeamActions::class, 'tagTeam', fn (): Model => TagTeam::factory()->suspended()->create()],
        'retired tag team' => [TagTeamActions::class, 'tagTeam', fn (): Model => TagTeam::factory()->retired()->create()],
        'inactive stable' => [StableActions::class, 'stable', fn (): Model => Stable::factory()->inactive()->create()],
        'active stable' => [StableActions::class, 'stable', fn (): Model => Stable::factory()->active()->create()],
        'retired stable' => [StableActions::class, 'stable', fn (): Model => Stable::factory()->retired()->create()],
        'undebuted title' => [TitleActions::class, 'title', fn (): Model => Title::factory()->undebuted()->create()],
        'active title' => [TitleActions::class, 'title', fn (): Model => Title::factory()->active()->create()],
        'inactive title' => [TitleActions::class, 'title', fn (): Model => Title::factory()->inactive()->create()],
        'retired title' => [TitleActions::class, 'title', fn (): Model => Title::factory()->retired()->create()],
        'deleted title' => [TitleActions::class, 'title', fn (): Model => Title::factory()->trashed()->create()],
    ]);
});
