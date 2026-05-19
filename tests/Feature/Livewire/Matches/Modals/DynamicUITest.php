<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Livewire\Matches\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Users\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
    $this->event = Event::factory()->create();
});

describe('Dynamic Match Type UI', function () {
    it('shows helper text when no match type is selected', function () {
        $component = Livewire::test(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal');

        $component->assertSee('Select a match type to configure competitors');
    });

    it('dynamically updates UI for Singles match', function () {
        $component = Livewire::test(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles);

        $component->assertSee('Competitor 1');
        $component->assertSee('Competitor 2');
        $component->assertDontSee('Competitor 3');
        $component->assertDontSee('Team A');
    });

    it('dynamically updates UI for Tag Team match', function () {
        $component = Livewire::test(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::TagTeam);

        $component->assertSee('Team A');
        $component->assertSee('Team B');
        $component->assertDontSee('Competitor 1');
    });

    it('dynamically updates UI for Triple Threat match', function () {
        $component = Livewire::test(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::TripleThreat);

        $component->assertSee('Competitor 1');
        $component->assertSee('Competitor 2');
        $component->assertSee('Competitor 3');
        $component->assertDontSee('Competitor 4');
        $component->assertDontSee('Team A');
    });

    it('dynamically updates UI for Fatal Four Way match', function () {
        $component = Livewire::test(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Fatal4Way);

        $component->assertSee('Competitor 1');
        $component->assertSee('Competitor 2');
        $component->assertSee('Competitor 3');
        $component->assertSee('Competitor 4');
        $component->assertDontSee('Team A');
    });

    it('dynamically updates UI for Battle Royal match', function () {
        $component = Livewire::test(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::BattleRoyal);

        $component->assertSee('Competitors (Select Multiple)');
        $component->assertSee('Select all wrestlers participating in this match');
        $component->assertDontSee('Competitor 1');
        $component->assertDontSee('Team A');
    });

    it('clears competitor data when match type changes', function () {
        $component = Livewire::test(FormModal::class, ['eventId' => $this->event->id])
            ->call('openModal')
            ->set('form.matchType', MatchType::Singles)
            ->set('form.matchType', MatchType::TagTeam);

        // Verify competitors were reinitialized for tag team (2 sides)
        $component->assertSet('form.competitors.0', ['wrestlers' => [], 'tag_teams' => []])
            ->assertSet('form.competitors.1', ['wrestlers' => [], 'tag_teams' => []]);
    });
});
