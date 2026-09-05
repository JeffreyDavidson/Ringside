<?php

declare(strict_types=1);

use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Livewire\Matches\Forms\MatchResultForm;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Wrestlers\Wrestler;
use JMac\Testing\Double;
use Livewire\Component;

/**
 * @return array{EventMatch, list<MatchCompetitor>}
 */
function createMatchForResultForm(): array
{
    $wrestlers = Wrestler::factory()->count(3)->create();
    $match = EventMatch::factory()
        ->withCompetitors($wrestlers->all())
        ->create(['match_type' => MatchType::BattleRoyal]);

    return [$match, $match->competitors()->orderBy('id')->get()->all()];
}

describe('match result form', function (): void {
    it('hydrates persisted result state', function (): void {
        // Arrange
        [$match, $competitors] = createMatchForResultForm();
        $eliminatedCompetitor = $competitors[0];
        $winner = $competitors[2];
        $match->update([
            'match_finish' => MatchFinish::Stipulation,
            'winning_side_id' => $winner->match_side_id,
        ]);
        $eliminatedCompetitor->forceFill([
            'elimination_order' => 1,
            'eliminated_by_match_competitor_id' => $winner->id,
        ])->save();
        $form = new MatchResultForm(Double::for(Component::class), 'form');

        // Act
        $form->fillFrom($match);

        // Assert
        expect($form->finish)->toBe(MatchFinish::Stipulation->value)
            ->and($form->winningSideId)->toBe($winner->match_side_id)
            ->and($form->eliminations[$eliminatedCompetitor->id])->toBe([
                'order' => 1,
                'eliminatedById' => $winner->id,
            ])
            ->and($form->eliminations[$winner->id])->toBe([
                'order' => null,
                'eliminatedById' => null,
            ]);
    });

    it('maps submitted result state to typed application data', function (): void {
        // Arrange
        [$match, $competitors] = createMatchForResultForm();
        $eliminatedCompetitor = $competitors[0];
        $winner = $competitors[2];
        $form = new MatchResultForm(Double::for(Component::class), 'form');
        $form->finish = MatchFinish::Stipulation->value;
        $form->winningSideId = $winner->match_side_id;
        $form->eliminations = [
            $eliminatedCompetitor->id => [
                'order' => '1',
                'eliminatedById' => (string) $winner->id,
            ],
            $competitors[1]->id => [
                'order' => null,
                'eliminatedById' => null,
            ],
            $winner->id => [
                'order' => null,
                'eliminatedById' => null,
            ],
        ];

        // Act
        $data = $form->toData($match);

        // Assert
        $elimination = $data->eliminations->sole();

        expect($data)->toBeInstanceOf(MatchResultData::class)
            ->and($data->finish)->toBe(MatchFinish::Stipulation)
            ->and($data->winningSide?->is($winner->side))->toBeTrue()
            ->and($elimination->competitor->is($eliminatedCompetitor))->toBeTrue()
            ->and($elimination->order)->toBe(1)
            ->and($elimination->eliminatedBy?->is($winner))->toBeTrue();
    });
});
