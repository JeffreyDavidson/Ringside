<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction as EmployManagerAction;
use App\Actions\Referees\EmployAction as EmployRefereeAction;
use App\Actions\Stables\DisbandAction as DisbandStableAction;
use App\Actions\TagTeams\ReleaseAction as ReleaseTagTeamAction;
use App\Actions\Titles\DebutAction;
use App\Actions\Wrestlers\EmployAction as EmployWrestlerAction;
use App\Livewire\Components\GeneralInfo;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('general info component', function (): void {
    test('it refreshes the whole card after the entity updated event', function (
        Closure $makeModel,
        string $event,
        Closure $transition,
        array $visibleBefore,
        Closure $visibleAfter,
    ): void {
        // Arrange
        $model = $makeModel();
        actingAs(administrator());
        $component = livewire(GeneralInfo::class, ['model' => $model]);

        foreach ($visibleBefore as $text) {
            $component->assertSee($text);
        }

        // Act
        $transition($model);
        $component->dispatch($event);

        // Assert
        foreach ($visibleBefore as $text) {
            $component->assertDontSee($text);
        }

        foreach ($visibleAfter() as $text) {
            $component->assertSee($text);
        }
    })->with([
        'wrestler status and start date' => [
            fn (): Model => Wrestler::factory()->unemployed()->create(),
            'wrestler-updated',
            fn (Wrestler $model) => app(EmployWrestlerAction::class)->handle($model, now()->subDays(10)),
            ['Unemployed', 'No Start Date Set'],
            fn (): array => ['Employed', now()->subDays(10)->toDateString()],
        ],
        'manager status and start date' => [
            fn (): Model => Manager::factory()->unemployed()->create(),
            'manager-updated',
            fn (Manager $model) => app(EmployManagerAction::class)->handle($model, now()->subDays(10)),
            ['Unemployed', 'No Start Date Set'],
            fn (): array => ['Employed', now()->subDays(10)->toDateString()],
        ],
        'referee status and start date' => [
            fn (): Model => Referee::factory()->unemployed()->create(),
            'referee-updated',
            fn (Referee $model) => app(EmployRefereeAction::class)->handle($model, now()->subDays(10)),
            ['Unemployed', 'No Start Date Set'],
            fn (): array => ['Employed', now()->subDays(10)->toDateString()],
        ],
        'stable status and current members' => [
            fn (): Model => Stable::factory()->active()->create(),
            'stable-updated',
            fn (Stable $model) => app(DisbandStableAction::class)->handle($model),
            ['Active'],
            fn (): array => ['Inactive'],
        ],
        'tag team status and current partners' => [
            fn (): Model => TagTeam::factory()->employed()->create(),
            'tag-team-updated',
            fn (TagTeam $model) => app(ReleaseTagTeamAction::class)->handle($model),
            ['Employed'],
            fn (): array => ['Released', 'No Current Wrestlers Assigned'],
        ],
        'title status and date introduced' => [
            fn (): Model => Title::factory()->undebuted()->create(),
            'title-updated',
            fn (Title $model) => app(DebutAction::class)->handle($model, now()->subDays(10)),
            ['Not Yet Debuted', 'No Start Date Set'],
            fn (): array => ['Active', now()->subDays(10)->toDateString()],
        ],
    ]);

    test('it renders the card for the mounted model', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->create(['hometown' => 'Parts Unknown']);
        actingAs(administrator());

        // Act
        $component = livewire(GeneralInfo::class, ['model' => $wrestler]);

        // Assert
        $component
            ->assertSee('General Info')
            ->assertSee('Parts Unknown')
            ->assertSee($wrestler->status->label());
    });

    test('it does not accept changes to its locked identifiers', function (string $property, mixed $value): void {
        // Arrange
        $wrestler = Wrestler::factory()->create();
        actingAs(administrator());
        $component = livewire(GeneralInfo::class, ['model' => $wrestler]);

        // Act
        $set = fn () => $component->set($property, $value);

        // Assert
        expect($set)->toThrow(Exception::class, "Cannot update locked property: [{$property}]");
    })->with([
        'model class' => ['modelClass', Title::class],
        'model id' => ['modelId', 99],
    ]);

    test('it loads relationships eagerly so a refresh runs a fixed number of queries', function (
        Closure $makeModel,
        string $event,
    ): void {
        // Arrange
        $refreshQueryCount = function (int $relatedCount) use ($makeModel, $event): int {
            $model = $makeModel($relatedCount);
            actingAs(administrator());
            $component = livewire(GeneralInfo::class, ['model' => $model]);

            DB::flushQueryLog();
            DB::enableQueryLog();

            $component->dispatch($event);

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        // Act
        $withOneRelated = $refreshQueryCount(1);
        $withManyRelated = $refreshQueryCount(4);

        // Assert
        expect($withManyRelated)->toBe($withOneRelated);
    })->with([
        'wrestler with current championships' => [
            function (int $count): Wrestler {
                $wrestler = Wrestler::factory()->employed()->create();
                TitleChampionship::factory()->count($count)->current()->forWrestler($wrestler)->create();

                return $wrestler;
            },
            'wrestler-updated',
        ],
        'tag team with current championships' => [
            function (int $count): TagTeam {
                $tagTeam = TagTeam::factory()->employed()->create();
                TitleChampionship::factory()->count($count)->current()->forTagTeam($tagTeam)->create();

                return $tagTeam;
            },
            'tag-team-updated',
        ],
        'stable with current members' => [
            function (int $count): Stable {
                $stable = Stable::factory()->active()->create();
                $stable->wrestlers()->attach(
                    Wrestler::factory()->employed()->count($count)->create(),
                    ['joined_at' => now()],
                );
                $stable->tagTeams()->attach(
                    TagTeam::factory()->employed()->count($count)->create(),
                    ['joined_at' => now()],
                );

                return $stable;
            },
            'stable-updated',
        ],
    ]);

    test('it labels a tag team by its injured and suspended members', function (bool $injured, bool $suspended): void {
        // Arrange
        $member = Wrestler::factory()->employed()->create();
        $tagTeam = TagTeam::factory()->employed()->withCurrentWrestlers(collect([$member]))->create();
        actingAs(administrator());

        if ($injured) {
            Injury::factory()->for($member, 'injurable')->create();
        }

        if ($suspended) {
            Suspension::factory()->for($member, 'suspendable')->create();
        }

        // Act
        $component = livewire(GeneralInfo::class, ['model' => $tagTeam]);

        // Assert
        expect($component->html())
            ->when($injured, fn ($html) => $html->toContain('data-test="availability-injured"'))
            ->unless($injured, fn ($html) => $html->not->toContain('data-test="availability-injured"'))
            ->when($suspended, fn ($html) => $html->toContain('data-test="availability-suspended"'))
            ->unless($suspended, fn ($html) => $html->not->toContain('data-test="availability-suspended"'));
    })->with([
        'injured member' => [true, false],
        'suspended member' => [false, true],
        'healthy team' => [false, false],
    ]);
});
