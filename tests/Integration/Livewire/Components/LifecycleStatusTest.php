<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction as EmployManagerAction;
use App\Actions\Referees\EmployAction as EmployRefereeAction;
use App\Actions\TagTeams\EmployAction as EmployTagTeamAction;
use App\Actions\Titles\DebutAction;
use App\Actions\Wrestlers\EmployAction as EmployWrestlerAction;
use App\Livewire\Components\LifecycleStatus;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('lifecycle status component', function (): void {
    test('it shows the fresh status after the entity updated event', function (
        Closure $makeModel,
        string $event,
        string $initialLabel,
        Closure $transition,
        string $newLabel,
    ): void {
        // Arrange
        $model = $makeModel();
        actingAs(administrator());
        $component = livewire(LifecycleStatus::class, ['model' => $model, 'updatedEvent' => $event]);
        $component->assertSee($initialLabel);

        // Act
        $transition($model);
        $component->dispatch($event);

        // Assert
        $component
            ->assertSee($newLabel)
            ->assertDontSee($initialLabel);
    })->with([
        'wrestler' => [
            fn (): Model => Wrestler::factory()->unemployed()->create(),
            'wrestler-updated',
            'Unemployed',
            fn (Wrestler $model) => app(EmployWrestlerAction::class)->handle($model),
            'Employed',
        ],
        'manager' => [
            fn (): Model => Manager::factory()->unemployed()->create(),
            'manager-updated',
            'Unemployed',
            fn (Manager $model) => app(EmployManagerAction::class)->handle($model),
            'Employed',
        ],
        'referee' => [
            fn (): Model => Referee::factory()->unemployed()->create(),
            'referee-updated',
            'Unemployed',
            fn (Referee $model) => app(EmployRefereeAction::class)->handle($model),
            'Employed',
        ],
        'tag team' => [
            fn (): Model => TagTeam::factory()->unemployed()->create(),
            'tag-team-updated',
            'Unemployed',
            fn (TagTeam $model) => app(EmployTagTeamAction::class)->handle($model),
            'Employed',
        ],
        'title' => [
            fn (): Model => Title::factory()->undebuted()->create(),
            'title-updated',
            'Not Yet Debuted',
            fn (Title $model) => app(DebutAction::class)->handle($model),
            'Active',
        ],
    ]);
});
