<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CreateAction;
use App\Data\Roster\UnavailabilityData;
use App\Livewire\Roster\Availability\Pages\Calendar;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('day navigation and typed search update the visible roster without interpreting invalid filters', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $actor = administrator();
    actingAs($actor);
    app(PromotionContextService::class)->set($promotion);
    app(PromotionContextService::class)->enforce();
    $wrestler = Wrestler::factory()->for($promotion)->create(['name' => 'Harbor Talent']);
    $referee = Referee::factory()->for($promotion)->create(['first_name' => 'River', 'last_name' => 'Official']);
    resolve(CreateAction::class)->handle($wrestler, new UnavailabilityData('2026-10-17', '2026-10-17', null), $actor);
    resolve(CreateAction::class)->handle($referee, new UnavailabilityData('2026-10-17', '2026-10-17', null), $actor);
    $event = Event::factory()->for($promotion)->create(['date' => '2026-10-17 18:00:00', 'name' => 'Harbor Saturday']);
    EventMatch::factory()->forEvent($event)->withCompetitors([$wrestler])->create();
    $calendar = livewire(Calendar::class);

    // Act / Assert
    $calendar->set('date', '2026-10-17')->assertSee('Harbor Talent')->assertSee('River Official')->assertSee('Harbor Saturday');
    $calendar->set('kind', 'referees')->assertSee('River Official');
    $calendar->set('kind', 'wrestlers')->set('search', 'Harbor')->assertSee('Harbor Talent')->assertDontSee('River Official');
    $calendar->call('moveDay', 1)->assertSet('date', '2026-10-18');
    $calendar->call('moveDay', -1)->assertSet('date', '2026-10-17');
    $calendar->call('moveDay', 20)->assertSet('date', '2026-10-17');
    $calendar->set('date', 'bad-date')->call('moveDay', 1)->assertHasErrors('date');
});
