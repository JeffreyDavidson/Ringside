<?php

declare(strict_types=1);

use App\Data\Matches\EventMatchData;
use App\Enums\MatchType;
use App\Livewire\Matches\Forms\CreateEditForm;
use App\Models\Matches\MatchStipulation;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use JMac\Testing\Double;
use Livewire\Component;

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
        $form->referees = $referees->modelKeys();
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
