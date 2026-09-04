<?php

declare(strict_types=1);

use App\Enums\Titles\TitleStatus;
use App\Enums\Titles\TitleType;
use App\Livewire\Titles\Tables\Main;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('TitlesTable Component', function () {

    beforeEach(function () {
        $this->user = administrator();
        actingAs($this->user);
    });

    describe('component rendering integration', function () {
        test('renders titles table with complete data relationships', function () {
            $activeTitle = Title::factory()->active()->singles()->create(['name' => 'World Championship']);
            $retiredTitle = Title::factory()->retired()->tagTeam()->create(['name' => 'Tag Team Titles']);
            $undebutedTitle = Title::factory()->create(['name' => 'Intercontinental Title']);

            $wrestler = Wrestler::factory()->bookable()->create(['name' => 'John Cena']);
            $tagTeam = TagTeam::factory()->bookable()->create(['name' => 'The Hardy Boyz']);

            TitleChampionship::factory()
                ->for($activeTitle, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create();

            TitleChampionship::factory()
                ->for($retiredTitle, 'title')
                ->for($tagTeam, 'champion')
                ->current()
                ->create();

            $component = livewire(Main::class);

            $component
                ->assertSee($activeTitle->name)
                ->assertSee($retiredTitle->name)
                ->assertSee($undebutedTitle->name)
                ->assertSee($wrestler->name)
                ->assertSee($tagTeam->name);
        });

        test('displays correct status badges for different title states', function () {
            $activeTitle = Title::factory()->active()->create(['name' => 'Active Title']);
            $inactiveTitle = Title::factory()->inactive()->create(['name' => 'Inactive Title']);
            $undebutedTitle = Title::factory()->create(['name' => 'Undebuted Title']);
            $retiredTitle = Title::factory()->retired()->create(['name' => 'Retired Title']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Active Title')
                ->assertSee('Inactive Title')
                ->assertSee('Undebuted Title')
                ->assertSee('Retired Title')
                ->assertSee('Retired');
        });
    });

    describe('filtering and search integration', function () {
        test('search functionality filters titles correctly', function () {
            Title::factory()->create(['name' => 'World Heavyweight Championship']);
            Title::factory()->create(['name' => 'Intercontinental Title']);
            Title::factory()->create(['name' => 'United States Championship']);

            $component = livewire(Main::class);

            $component
                ->set('search', 'World')
                ->assertSee('World Heavyweight Championship')
                ->assertDontSee('Intercontinental Title')
                ->assertDontSee('United States Championship');

            $component
                ->set('search', '')
                ->assertSee('World Heavyweight Championship')
                ->assertSee('Intercontinental Title')
                ->assertSee('United States Championship');
        });

        test('status filter functionality works with real data', function () {
            $titles = [
                TitleStatus::Undebuted->value => Title::factory()->undebuted()->create(['name' => 'Undebuted Title']),
                TitleStatus::PendingDebut->value => Title::factory()->withFutureDebut()->create(['name' => 'Pending Title']),
                TitleStatus::Active->value => Title::factory()->active()->create(['name' => 'Active Title']),
                TitleStatus::Inactive->value => Title::factory()->inactive()->create(['name' => 'Inactive Title']),
                TitleStatus::Retired->value => Title::factory()->retired()->create(['name' => 'Retired Title']),
            ];

            foreach ($titles as $status => $visibleTitle) {
                $component = livewire(Main::class)
                    ->set('filterValues.status', $status)
                    ->assertSee($visibleTitle->name);

                foreach ($titles as $otherStatus => $hiddenTitle) {
                    if ($otherStatus !== $status) {
                        $component->assertDontSee($hiddenTitle->name);
                    }
                }
            }
        });

        test('type filter integration works correctly', function () {
            $singlesTitle = Title::factory()->singles()->create(['name' => 'Singles Title']);
            $tagTeamTitle = Title::factory()->tagTeam()->create(['name' => 'Tag Team Titles']);

            $component = livewire(Main::class);

            $component
                ->set('filterValues.type', TitleType::Singles->value)
                ->assertSee($singlesTitle->name)
                ->assertDontSee($tagTeamTitle->name)
                ->set('filterValues.type', TitleType::TagTeam->value)
                ->assertSee($tagTeamTitle->name)
                ->assertDontSee($singlesTitle->name);
        });
    });

    describe('action integration', function () {
        test('failed debut remains on the titles table', function () {
            $title = Title::factory()->active()->create();

            livewire(Main::class)
                ->call('debut', $title)
                ->assertNoRedirect();
        });

        test('failed pull remains on the titles table', function () {
            $title = Title::factory()->inactive()->create();

            livewire(Main::class)
                ->call('putOnHold', $title)
                ->assertNoRedirect();
        });

        test('failed retirement remains on the titles table', function () {
            $title = Title::factory()->retired()->create();

            livewire(Main::class)
                ->call('retire', $title)
                ->assertNoRedirect();
        });

        test('failed unretirement remains on the titles table', function () {
            $title = Title::factory()->active()->create();

            livewire(Main::class)
                ->call('unretire', $title)
                ->assertNoRedirect();
        });

        test('failed reinstatement remains on the titles table', function () {
            $title = Title::factory()->active()->create();

            livewire(Main::class)
                ->call('reinstate', $title)
                ->assertNoRedirect();
        });
    });

    describe('championship integration', function () {
        test('displays current champions correctly', function () {
            $title = Title::factory()->active()->create(['name' => 'World Championship']);
            $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Current Champion']);

            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create();

            $component = livewire(Main::class);

            $component
                ->assertSee('World Championship')
                ->assertSee('Current Champion');
        });

        test('handles vacant titles correctly', function () {
            $vacantTitle = Title::factory()->active()->create(['name' => 'Vacant Championship']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Vacant Championship')
                ->assertSee('Vacant');
        });

        test('displays championship history integration', function () {
            $title = Title::factory()->active()->create(['name' => 'Historical Title']);
            $wrestler1 = Wrestler::factory()->create(['name' => 'Former Champion']);
            $wrestler2 = Wrestler::factory()->create(['name' => 'Current Champion']);

            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler1, 'champion')
                ->ended()
                ->create();

            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler2, 'champion')
                ->current()
                ->create();

            $component = livewire(Main::class);

            $component
                ->assertSee('Historical Title')
                ->assertSee('Current Champion')
                ->assertDontSee('Former Champion');
        });
    });

    describe('data loading integration', function () {
        test('builder eager loads the current champion relationship', function () {
            $title = Title::factory()->active()->create(['name' => 'Championship Title']);
            $wrestler = Wrestler::factory()->create(['name' => 'Champion Wrestler']);

            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create();

            $loadedTitle = app(Main::class)->builder()->findOrFail($title->id);

            expect($loadedTitle->relationLoaded('currentChampionship'))->toBeTrue()
                ->and($loadedTitle->currentChampionship?->relationLoaded('champion'))->toBeTrue();
        });
    });

    describe('real-time updates integration', function () {
        test('component updates when title data changes', function () {
            $title = Title::factory()->create(['name' => 'Original Name']);

            $component = livewire(Main::class);
            $component->assertSee('Original Name');

            $title->update(['name' => 'Updated Name']);

            $component->call('$refresh');
            $component->assertSee('Updated Name');
            $component->assertDontSee('Original Name');
        });

        test('component reflects championship changes', function () {
            $title = Title::factory()->active()->create(['name' => 'Championship']);
            $wrestler = Wrestler::factory()->create(['name' => 'New Champion']);

            $component = livewire(Main::class);
            $component->assertSee('Vacant');

            TitleChampionship::factory()
                ->for($title, 'title')
                ->for($wrestler, 'champion')
                ->current()
                ->create();

            $component->call('$refresh');
            $component->assertSee('New Champion');
        });
    });
});
