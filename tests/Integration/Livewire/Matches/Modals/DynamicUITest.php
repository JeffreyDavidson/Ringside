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
});

describe('dynamic match type UI', function (): void {
    it('locks the event context against client-side changes', function (): void {
        $event = Event::factory()->create();

        // Arrange
        $otherEvent = Event::factory()->create();
        $component = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act / Assert
        expect(fn () => $component->set('eventId', $otherEvent->id))
            ->toThrow(CannotUpdateLockedPropertyException::class);
    });

    it('shows helper text when no match type is selected', function (): void {
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act

        // Assert
        $component->assertSee('Select a match type to configure competitors');
    });

    it('renders the competitor controls for :dataset matches', function (
        MatchType $matchType,
        array $visibleText,
        array $hiddenText,
    ): void {
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
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
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $component->set('form.matchType', MatchType::Singles);
        $component->set('form.competitors.0.wrestlers', [123]);
        $component->set('form.matchType', MatchType::TagTeam);

        // Assert
        $component
            ->assertSet('form.competitors.0', ['wrestlers' => [], 'tag_teams' => []])
            ->assertSet('form.competitors.1', ['wrestlers' => [], 'tag_teams' => []]);
    });

    it('does not allow tag teams before a match type is selected', function (): void {
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act

        // Assert
        $component->assertSet('matchTypeAllowsTagTeams', false);
    });

    it('resets competitors when the match type arrives as its string value', function (): void {
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);
        $component->set('form.matchType', MatchType::Singles);
        $component->set('form.competitors.0.wrestlers', [123]);

        // Act
        $component->set('form.matchType', MatchType::TagTeam->value);

        // Assert
        $component
            ->assertSet('form.competitors.0', ['wrestlers' => [], 'tag_teams' => []])
            ->assertSet('form.competitors.1', ['wrestlers' => [], 'tag_teams' => []]);
    });

    it('keeps competitors when the match type is cleared', function (): void {
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);
        $component->set('form.matchType', MatchType::Singles);
        $component->set('form.competitors.0.wrestlers', [123]);

        // Act
        $component->set('form.matchType', null);

        // Assert
        $component
            ->assertSet('form.matchType', null)
            ->assertSet('form.competitors.0.wrestlers', [123]);
    });

    it('rejects a tampered match type value', function (): void {
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act / Assert
        expect(fn () => $component->set('form.matchType', 'not-a-match-type'))
            ->toThrow(ValueError::class);
    });
});

describe('accessible competitor fields', function (): void {
    it('describes each roster search box with its usage hint', function (): void {
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $component->set('form.matchType', MatchType::Singles);

        // Assert
        $component
            ->assertSeeHtml('aria-describedby="form.competitors.0.wrestlers.0-hint"')
            ->assertSeeHtml('id="form.competitors.0.wrestlers.0-hint"')
            ->assertSeeHtml('aria-describedby="form.referees-hint"')
            ->assertDontSeeHtml('form.competitors.0.wrestlers.0-error');
    });

    it('links a single competitor error to its search box', function (): void {
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);
        $component->set('form.matchType', MatchType::Singles);

        // Act
        $component->call('save');

        // Assert
        $component
            ->assertSeeHtml('aria-describedby="form.competitors.0.wrestlers.0-hint form.competitors.0.wrestlers.0-error"')
            ->assertSeeHtml('id="form.competitors.0.wrestlers.0-error"')
            ->assertSee('Choose a wrestler for Competitor 1.')
            ->assertSeeHtml('aria-describedby="form.referees-hint form.referees-error"');
    });

    it('groups each tag team side under its own name', function (): void {
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);
        $component->set('form.matchType', MatchType::TagTeam);

        // Act
        $component->call('save');

        // Assert
        $component
            ->assertSeeHtmlInOrder(['<fieldset', 'aria-describedby="form.competitors.0-error"', '<legend', 'Team A'])
            ->assertSeeHtmlInOrder(['<fieldset', 'aria-describedby="form.competitors.1-error"', '<legend', 'Team B'])
            ->assertSeeHtml('id="form.competitors.0-error"')
            ->assertSee('Add wrestlers or a tag team to Team A.')
            ->assertSeeHtml('<span class="sr-only">Team B</span>');
    });

    it('names each side of a :dataset match', function (MatchType $matchType, array $legends): void {
        $event = Event::factory()->create();

        // Arrange
        $component = livewire(FormModal::class, ['eventId' => $event->id]);

        // Act
        $component->set('form.matchType', $matchType);

        // Assert
        foreach ($legends as $legend) {
            $component->assertSeeHtmlInOrder(['<legend', $legend, '<span class="sr-only">'.$legend.'</span>']);
        }
    })->with([
        'two on one handicap' => [MatchType::TwoOnOneHandicap, ['Side 1', 'Side 2']],
        'gauntlet' => [MatchType::Gauntlet, ['Side 1', 'Side 2']],
    ]);
});
