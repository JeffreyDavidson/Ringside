<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Livewire\TagTeams\Tables\Main;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('tag teams table', function (): void {
    it('renders the configured table controls and tag team attributes', function (): void {
        // Arrange
        TagTeam::factory()->employed()->create(['name' => 'The Hardy Boyz']);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Add Tag Team')
            ->assertSeeHtml('placeholder="Search tag teams"')
            ->assertSeeHtml('aria-label="Actions for The Hardy Boyz"')
            ->assertSeeHtml('wire:confirm="Remove The Hardy Boyz?"')
            ->assertSeeHtml('role="group"')
            ->assertSee('The Hardy Boyz')
            ->assertSee(EmploymentStatus::Employed->label());
    });

    it('labels suspended tag teams without changing their employment status', function (): void {
        // Arrange
        $tagTeam = TagTeam::factory()->employed()->create();
        Suspension::factory()->for($tagTeam, 'suspendable')->create();

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertSee('Employed');

        expect($component->html())
            ->toContain('data-test="availability-suspended"')
            ->not->toContain('data-test="availability-injured"');
    });

    it('labels tag teams by injured and suspended members', function (bool $injured, bool $suspended): void {
        // Arrange
        $healthy = Wrestler::factory()->employed()->create();
        $member = Wrestler::factory()->employed()->create();
        TagTeam::factory()->employed()->withCurrentWrestlers(collect([$healthy, $member]))->create();

        if ($injured) {
            Injury::factory()->for($member, 'injurable')->create();
        }

        if ($suspended) {
            Suspension::factory()->for($member, 'suspendable')->create();
        }

        // Act
        $component = livewire(Main::class);

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

    it('does not query member availability per tag team row', function (): void {
        // Arrange
        $queryCount = function (int $teams): int {
            TagTeam::query()->each(fn (TagTeam $tagTeam) => $tagTeam->forceDelete());

            for ($i = 0; $i < $teams; $i++) {
                $members = Wrestler::factory()->employed()->count(2)->create();
                $members->each(fn (Wrestler $wrestler) => Injury::factory()->for($wrestler, 'injurable')->create());
                TagTeam::factory()->employed()->withCurrentWrestlers($members)->create();
            }

            $component = livewire(Main::class);
            DB::flushQueryLog();
            DB::enableQueryLog();
            $component->call('$refresh');
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        // Act
        $withOneTeam = $queryCount(1);
        $withManyTeams = $queryCount(4);

        // Assert
        expect($withManyTeams)->toBe($withOneTeam);
    });
});
