<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Livewire\Matches\Modals\FormModal;
use App\Models\Events\Event;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
    $this->event = Event::factory()->create();
});

describe('dynamic match type UI', function (): void {
    it('locks the event context against client-side changes', function (): void {
        // Arrange
        $otherEvent = Event::factory()->create();
        $component = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act / Assert
        expect(fn () => $component->set('eventId', $otherEvent->id))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    });

    it('shows helper text when no match type is selected', function (): void {
        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $component->call('openModal');

        // Assert
        $component->assertSee('Select a match type to configure competitors');
    });

    it('renders the competitor controls for :dataset matches', function (
        MatchType $matchType,
        array $visibleText,
        array $hiddenText,
    ): void {
        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $component->call('openModal');
        $component->set('form.matchType', $matchType);

        // Assert
        foreach ($visibleText as $text) {
            $component->assertSee($text);
        }

        foreach ($hiddenText as $text) {
            $component->assertDontSee($text);
        }
    })->with([
        'singles' => [
            MatchType::Singles,
            ['Competitor 1', 'Competitor 2'],
            ['Competitor 3', 'Team A'],
        ],
        'tag team' => [
            MatchType::TagTeam,
            ['Team A', 'Team B'],
            ['Competitor 1'],
        ],
        'triple threat' => [
            MatchType::TripleThreat,
            ['Competitor 1', 'Competitor 2', 'Competitor 3'],
            ['Competitor 4', 'Team A'],
        ],
        'fatal four way' => [
            MatchType::Fatal4Way,
            ['Competitor 1', 'Competitor 2', 'Competitor 3', 'Competitor 4'],
            ['Team A'],
        ],
        'battle royal' => [
            MatchType::BattleRoyal,
            ['Competitors (Select Multiple)', 'Select all wrestlers participating in this match'],
            ['Competitor 1', 'Team A'],
        ],
    ]);

    it('clears competitor data when the match type changes', function (): void {
        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $component->call('openModal');
        $component->set('form.matchType', MatchType::Singles);
        $component->set('form.competitors.0.wrestlers', [123]);
        $component->set('form.matchType', MatchType::TagTeam);

        // Assert
        $component
            ->assertSet('form.competitors.0', ['wrestlers' => [], 'tag_teams' => []])
            ->assertSet('form.competitors.1', ['wrestlers' => [], 'tag_teams' => []]);
    });
});
