<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Services\Promotions\PromotionContextService;
use App\ViewModels\DashboardViewModel;

describe('upcoming events', function (): void {
    it('lists the next scheduled events soonest first with their match counts', function (): void {
        // Arrange
        $later = Event::factory()->create(['date' => now()->addDays(20)]);
        $soonest = Event::factory()->create(['date' => now()->addDays(2)]);
        $middle = Event::factory()->create(['date' => now()->addDays(9)]);
        Event::factory()->create(['date' => now()->addDays(40)]);
        Event::factory()->past()->create();
        Event::factory()->unscheduled()->create();
        EventMatch::factory()->forEvent($soonest)->count(2)->create();

        // Act
        $events = app(DashboardViewModel::class)->data()['upcomingEvents'];

        // Assert
        expect($events->modelKeys())->toBe([$soonest->id, $middle->id, $later->id])
            ->and($events->first()?->matches_count)->toBe(2)
            ->and($events->first()?->relationLoaded('venue'))->toBeTrue();
    });
});

describe('roster availability', function (): void {
    it('counts employed wrestlers and who is available, injured or suspended', function (): void {
        // Arrange
        Wrestler::factory()->employed()->count(3)->create();
        Wrestler::factory()->injured()->create();
        Wrestler::factory()->suspended()->count(2)->create();
        Wrestler::factory()->unemployed()->create();
        Wrestler::factory()->retired()->create();

        // Act
        $availability = app(DashboardViewModel::class)->data()['availability'];

        // Assert
        expect($availability)->toBe([
            'employed' => 6,
            'available' => 3,
            'injured' => 1,
            'suspended' => 2,
        ]);
    });

    it('counts availability in one query that carries ids and flags instead of every wrestler column', function (): void {
        // Arrange
        Wrestler::factory()->employed()->count(2)->create();
        Wrestler::factory()->injured()->create();
        $viewModel = app(DashboardViewModel::class);

        // Act
        $statements = collect(recordStatements(fn (): array => $viewModel->data()))
            ->filter(fn (array $statement): bool => str_contains($statement['sql'], 'employed_wrestlers'));

        // Assert
        expect($statements->sole()['sql'])->not->toContain('"wrestlers".*');
    });

    it('reports zero for every count when nobody is under contract', function (): void {
        // Arrange
        Wrestler::factory()->unemployed()->create();

        // Act
        $availability = app(DashboardViewModel::class)->data()['availability'];

        // Assert
        expect($availability)->toBe([
            'employed' => 0,
            'available' => 0,
            'injured' => 0,
            'suspended' => 0,
        ]);
    });
});

describe('current champions', function (): void {
    it('lists active titles with a current champion ordered by name', function (): void {
        // Arrange
        $tagTitle = Title::factory()->active()->create(['name' => 'Tag Team Championship']);
        $heavyweightTitle = Title::factory()->active()->create(['name' => 'Heavyweight Championship']);
        $retiredTitle = Title::factory()->retired()->create(['name' => 'Cruiserweight Championship']);
        $vacantTitle = Title::factory()->active()->create(['name' => 'Womens Championship']);
        TitleChampionship::factory()->for($tagTitle)->forTagTeam()->current()->create();
        $heavyweightReign = TitleChampionship::factory()->for($heavyweightTitle)->forWrestler()->current()->create();
        TitleChampionship::factory()->for($heavyweightTitle)->forWrestler()->ended()->create();
        TitleChampionship::factory()->for($retiredTitle)->forWrestler()->current()->create();
        TitleChampionship::factory()->for($vacantTitle)->forWrestler()->ended()->create();

        // Act
        $champions = app(DashboardViewModel::class)->data()['champions'];

        // Assert
        expect(array_map(fn (array $champion): int => $champion['title']->id, $champions))->toBe([$heavyweightTitle->id, $tagTitle->id])
            ->and($champions[0]['championship']->is($heavyweightReign))->toBeTrue();
    });
});

describe('champions list cap', function (): void {
    it('lists at most twelve championed titles', function (): void {
        // Arrange
        foreach (range(1, 13) as $number) {
            TitleChampionship::factory()
                ->for(Title::factory()->active()->create(['name' => sprintf('Title %02d', $number)]))
                ->forWrestler()
                ->current()
                ->create();
        }

        // Act
        $champions = app(DashboardViewModel::class)->data()['champions'];

        // Assert
        expect($champions)->toHaveCount(12)
            ->and($champions[11]['title']->name)->toBe('Title 12');
    });
});

describe('promotion scope', function (): void {
    it('only includes records from the active promotion', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $otherPromotion = Promotion::factory()->create();
        $ownEvent = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
        Event::factory()->for($otherPromotion, 'promotion')->scheduled()->create();
        Wrestler::factory()->employed()->for($promotion, 'promotion')->create();
        Wrestler::factory()->employed()->for($otherPromotion, 'promotion')->create();
        $context = app(PromotionContextService::class);
        $context->set($promotion);
        $context->enforce();

        // Act
        $dashboard = app(DashboardViewModel::class)->data();

        // Assert
        expect($dashboard['upcomingEvents']->modelKeys())->toBe([$ownEvent->id])
            ->and($dashboard['availability']['employed'])->toBe(1);
    });
});

describe('champion display helpers', function (): void {
    it('links each champion to their roster page and reports the reign length', function (): void {
        // Arrange
        $champion = Wrestler::factory()->create();
        TitleChampionship::factory()
            ->for(Title::factory()->active())
            ->forWrestler($champion)
            ->current()
            ->create(['won_at' => now()->subDays(30)]);

        // Act
        $row = app(DashboardViewModel::class)->data()['champions'][0];

        // Assert
        expect($row['url'])->toBe(route('wrestlers.show', $champion))
            ->and($row['days'])->toBe(30);
    });
});

describe('champion reign length defence', function (): void {
    it('never reports a negative reign length', function (): void {
        // Arrange
        TitleChampionship::factory()
            ->for(Title::factory()->active())
            ->forWrestler(Wrestler::factory()->create())
            ->current()
            ->create(['won_at' => now()->addDays(10)]);

        // Act
        $days = app(DashboardViewModel::class)->data()['champions'][0]['days'];

        // Assert
        expect($days)->toBe(0);
    });
});
