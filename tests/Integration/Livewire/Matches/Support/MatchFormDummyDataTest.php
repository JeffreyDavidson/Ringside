<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Livewire\Matches\Forms\CreateEditForm;
use App\Livewire\Matches\Support\MatchFormDummyData;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use JMac\Testing\Double;
use Livewire\Component;

describe('match form dummy data', function (): void {
    it('fills a valid singles match with bookable roster members', function (): void {
        // Arrange
        $bookableWrestlers = Wrestler::factory()->count(2)->bookable()->create();
        $unavailableWrestler = Wrestler::factory()->suspended()->create();
        $bookableReferee = Referee::factory()->bookable()->create();
        $unavailableReferee = Referee::factory()->suspended()->create();
        $form = new CreateEditForm(Double::for(Component::class), 'form');
        $dummyData = new MatchFormDummyData(app(RosterBookingEligibility::class));

        // Act
        $dummyData->fill($form);

        // Assert
        $selectedWrestlerIds = collect($form->competitors)
            ->flatMap(fn (array $side): array => $side['wrestlers'] ?? [])
            ->sort()
            ->values()
            ->all();

        expect($form->matchType)->toBe(MatchType::Singles)
            ->and($form->competitors)->toHaveCount(2)
            ->and($selectedWrestlerIds)->toBe($bookableWrestlers->pluck('id')->sort()->values()->all())
            ->and($selectedWrestlerIds)->not->toContain($unavailableWrestler->id)
            ->and($form->referees)->toBe([$bookableReferee->id])
            ->and($form->referees)->not->toContain($unavailableReferee->id)
            ->and($form->titles)->toBe([])
            ->and($form->preview)->toBeString()->not->toBeEmpty();
    });
});
