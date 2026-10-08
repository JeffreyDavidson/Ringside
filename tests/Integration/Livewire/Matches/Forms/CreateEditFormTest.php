<?php

declare(strict_types=1);

use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Livewire\Matches\Forms\CreateEditForm;
use App\Livewire\Matches\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use JMac\Testing\Double;
use Livewire\Component;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('match create and edit form', function (): void {
    it('resets competitor inputs for the selected match shape', function (MatchType $matchType, int $sideCount): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');

        // Act
        $form->resetCompetitorsFor($matchType);

        // Assert
        expect($form->competitors)->toBe(array_fill(0, $sideCount, [
            'wrestlers' => [],
            'tag_teams' => [],
        ]));
    })->with([
        'two-sided match' => [MatchType::Singles, 2],
        'three-sided match' => [MatchType::TripleThreat, 3],
        'individual-entrant match' => [MatchType::BattleRoyal, 1],
    ]);

    it('maps an individual-entrant match to typed application data', function (): void {
        // Arrange
        $wrestlers = Wrestler::factory()->count(3)->create();
        $referees = Referee::factory()->count(2)->create();
        $title = Title::factory()->active()->singles()->create();
        $stipulation = MatchStipulation::factory()->active()->create();
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->matchType = MatchType::BattleRoyal;
        $form->matchStipulationId = $stipulation->id;
        $form->competitors = [[
            'wrestlers' => $wrestlers->modelKeys(),
            'tag_teams' => [],
        ]];
        $form->referees = array_reverse($referees->modelKeys());
        $form->titles = [$title->id];
        $form->preview = 'Every competitor enters for themselves.';

        // Act
        $data = $form->toData();

        // Assert
        $sideWrestlerIds = $data->sides
            ->flatMap(fn (array $side): array => array_map(
                fn (Wrestler $wrestler): int => $wrestler->id,
                $side['wrestlers'] ?? [],
            ))
            ->all();

        expect($data)->toBeInstanceOf(EventMatchData::class)
            ->and($data->matchType)->toBe(MatchType::BattleRoyal)
            ->and($data->referees->modelKeys())->toBe($referees->modelKeys())
            ->and($data->titles->modelKeys())->toBe([$title->id])
            ->and($data->sides)->toHaveCount(3)
            ->and($sideWrestlerIds)->toBe($wrestlers->modelKeys())
            ->and($data->sides->every(fn (array $side): bool => count($side['wrestlers'] ?? []) === 1))->toBeTrue()
            ->and($data->preview)->toBe('Every competitor enters for themselves.')
            ->and($data->matchStipulation?->is($stipulation))->toBeTrue();
    });
});

describe('match form side order', function (): void {
    it('maps each side\'s selected wrestlers in id order whatever order they were picked in', function (): void {
        // Arrange
        [$first, $second, $third, $fourth] = Wrestler::factory()->count(4)->create()->all();
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->matchType = MatchType::TagTeam;
        $form->competitors = [
            ['wrestlers' => [$second->id, $first->id], 'tag_teams' => []],
            ['wrestlers' => [$fourth->id, $third->id], 'tag_teams' => []],
        ];
        $form->referees = [Referee::factory()->create()->id];

        // Act
        $data = $form->toData();

        // Assert
        $sideWrestlerIds = $data->sides
            ->map(fn (array $side): array => array_map(fn (Wrestler $wrestler): int => $wrestler->id, $side['wrestlers'] ?? []))
            ->all();

        expect($sideWrestlerIds)->toBe([
            1 => [$first->id, $second->id],
            2 => [$third->id, $fourth->id],
        ]);
    });
});

describe('match form side labels', function (): void {
    it('names a :dataset side for the form and its validation messages', function (?MatchType $matchType, string $expectedLabel): void {
        // Arrange
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $form->matchType = $matchType;

        // Act
        $label = $form->sideLabel(1);

        // Assert
        expect($label)->toBe($expectedLabel);
    })->with([
        'singles' => [MatchType::Singles, 'Competitor 2'],
        'triple threat' => [MatchType::TripleThreat, 'Competitor 2'],
        'tag team' => [MatchType::TagTeam, 'Team B'],
        'battle royal' => [MatchType::BattleRoyal, 'Competitors'],
        'handicap' => [MatchType::TwoOnOneHandicap, 'Side 2'],
        'unselected match type' => [null, 'Side 2'],
    ]);
});

describe('match form validation feedback', function (): void {
    beforeEach(function (): void {
        actingAs(administrator());
        $this->event = Event::factory()->create();
    });

    it('books a tag team match when the comboboxes send an empty wrestler list for each side', function (): void {
        // Arrange
        $tagTeamIds = TagTeam::factory()->count(2)->bookable()->create()->modelKeys();
        $referee = Referee::factory()->bookable()->create();
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);

        // Act
        $modal->set('form.matchType', MatchType::TagTeam);
        $modal->set([
            'form.competitors' => [
                ['wrestlers' => [], 'tag_teams' => [$tagTeamIds[0]]],
                ['wrestlers' => [], 'tag_teams' => [$tagTeamIds[1]]],
            ],
            'form.referees' => [$referee->id],
        ]);
        $modal->call('save');

        // Assert
        $modal->assertHasNoErrors();
        expect(EventMatch::query()->whereBelongsTo($this->event)->sole()->tagTeams()->count())->toBe(2);
    });

    it('explains what a :dataset is missing in plain words', function (
        MatchType $matchType,
        array $competitors,
        array $expectedMessages,
        array $absentErrors,
    ): void {
        // Arrange
        $modal = livewire(FormModal::class, ['eventId' => $this->event->id]);
        $modal->set('form.matchType', $matchType);

        // Act
        if ($competitors !== []) {
            $modal->set('form.competitors', $competitors);
        }
        $modal->call('save');

        // Assert
        $errors = $modal->instance()->getErrorBag();
        foreach ($expectedMessages as $field => $message) {
            expect($errors->first($field))->toBe($message);
        }
        foreach ($absentErrors as $field) {
            expect($errors->has($field))->toBeFalse();
        }
        expect(implode(' ', $errors->all()))->not->toContain('competitors.');
    })->with([
        'tag team with empty sides' => [
            MatchType::TagTeam,
            [],
            [
                'form.competitors.0' => 'Add wrestlers or a tag team to Team A.',
                'form.competitors.1' => 'Add wrestlers or a tag team to Team B.',
            ],
            ['form.competitors.0.wrestlers', 'form.competitors.0.tag_teams'],
        ],
        'tag team side with one wrestler' => [
            MatchType::TagTeam,
            [['wrestlers' => [1], 'tag_teams' => []], ['wrestlers' => [], 'tag_teams' => [1]]],
            ['form.competitors.0.wrestlers' => 'Add at least 2 wrestlers or a tag team to Team A.'],
            ['form.competitors.0.tag_teams', 'form.competitors.1.wrestlers'],
        ],
        'singles match' => [
            MatchType::Singles,
            [],
            [
                'form.competitors.0.wrestlers' => 'Choose a wrestler for Competitor 1.',
                'form.competitors.1.wrestlers' => 'Choose a wrestler for Competitor 2.',
            ],
            [],
        ],
        'triple threat match' => [
            MatchType::TripleThreat,
            [],
            ['form.competitors.2.wrestlers' => 'Choose a wrestler for Competitor 3.'],
            [],
        ],
        'handicap match' => [
            MatchType::TwoOnOneHandicap,
            [],
            ['form.competitors.1.wrestlers' => 'Add wrestlers or a tag team to Side 2.'],
            [],
        ],
        'battle royal without entrants' => [
            MatchType::BattleRoyal,
            [],
            ['form.competitors.0.wrestlers' => 'Choose the wrestlers competing in this match.'],
            [],
        ],
        'battle royal with too few entrants' => [
            MatchType::BattleRoyal,
            [['wrestlers' => [1, 2], 'tag_teams' => []]],
            ['form.competitors.0.wrestlers' => 'Choose at least 3 wrestlers for this match.'],
            [],
        ],
        'royal rumble with too many entrants' => [
            MatchType::RoyalRumble,
            [['wrestlers' => range(1, 31), 'tag_teams' => []]],
            ['form.competitors.0.wrestlers' => 'Choose no more than 30 wrestlers for this match.'],
            [],
        ],
        'singles match with one wrestler on both sides' => [
            MatchType::Singles,
            [['wrestlers' => [1], 'tag_teams' => []], ['wrestlers' => [1], 'tag_teams' => []]],
            ['form.competitors.1.wrestlers.0' => 'A wrestler can only be booked once in a match.'],
            [],
        ],
        'tag team match with one team on both sides' => [
            MatchType::TagTeam,
            [['wrestlers' => [], 'tag_teams' => [1]], ['wrestlers' => [], 'tag_teams' => [1]]],
            ['form.competitors.1.tag_teams.0' => 'A tag team can only be booked once in a match.'],
            [],
        ],
        'singles match with an unknown wrestler' => [
            MatchType::Singles,
            [['wrestlers' => [PHP_INT_MAX], 'tag_teams' => []], ['wrestlers' => [], 'tag_teams' => []]],
            ['form.competitors.0.wrestlers.0' => 'The selected wrestler is invalid.'],
            [],
        ],
    ]);
});
