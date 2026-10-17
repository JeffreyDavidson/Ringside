<?php

declare(strict_types=1);

use App\Enums\Matches\MatchType;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Matches\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('checks the current selections without saving a match', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $modal = livewire(FormModal::class, ['eventId' => $event->id]);

    // Act
    $modal->set('form.matchType', MatchType::Singles);
    $modal->set('form.competitors', [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]]);
    $modal->call('checkHistory');

    // Assert
    $modal->assertReturned(fn (array $history): bool => $history['available'] && count($history['subjects']) === 2);
    expect($event->matches()->count())->toBe(0);
});

it('returns the history with links to the profiles it names', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $modal = livewire(FormModal::class, ['eventId' => $event->id]);
    $modal->set('form.matchType', MatchType::Singles);
    $modal->set('form.competitors', [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]]);

    // Act
    $modal->call('checkHistory');

    // Assert
    $modal->assertReturned(fn (array $history): bool => $history['subjects'][0]['url'] === route('wrestlers.show', $first)
        && $history['subjects'][1]['url'] === route('wrestlers.show', $second));
});

it('requires booking permission again when history is checked', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
    $modal = livewire(FormModal::class, ['eventId' => $event->id]);
    actingAs(basicUser());

    // Act
    $modal->call('checkHistory');

    // Assert
    $modal->assertForbidden();
});

it('returns a useful prompt for absent or malformed selections', function (): void {
    // Arrange
    actingAs(administrator());
    $event = Event::factory()->for(Promotion::factory(), 'promotion')->scheduled()->create();
    $modal = livewire(FormModal::class, ['eventId' => $event->id]);

    // Act
    $modal->call('checkHistory');

    // Assert
    $modal->assertReturned(['error' => __('matches.history.selection_required')]);

    // Act
    $modal->set('form.matchType', MatchType::Singles);
    $modal->call('checkHistory');

    // Assert
    $modal->assertReturned(['error' => __('matches.history.unavailable_selection')]);
});

it('uses the edited match event and rejects a foreign selection even for a global administrator', function (): void {
    // Arrange
    actingAs(administrator());
    [$promotion, $foreign] = Promotion::factory()->count(2)->create()->all();
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
    $wrongEvent = Event::factory()->for($foreign, 'promotion')->scheduled()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $foreignWrestler = Wrestler::factory()->for($foreign, 'promotion')->create();
    $match = EventMatch::factory()->for($event)->withCompetitors([$first, $second])->create();
    $modal = livewire(FormModal::class, ['eventId' => $wrongEvent->id, 'modelId' => $match->id]);

    // Act
    $modal->call('checkHistory');

    // Assert
    $modal->assertReturned(fn (array $history): bool => $history['available'] && $history['subjects'][0]['name'] === $first->name);

    // Act
    $modal->set('form.competitors', [['wrestlers' => [$first->id]], ['wrestlers' => [$foreignWrestler->id]]]);
    $modal->call('checkHistory');

    // Assert
    $modal->assertReturned(['error' => __('matches.history.unavailable_selection')]);
    expect($match->fresh()->competitors()->count())->toBe(2);
});

it('allows active owners and managers to check their promotion history', function (MembershipRole $role): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $modal = livewire(FormModal::class, ['eventId' => $event->id]);
    $modal->set('form.matchType', MatchType::Singles);
    $modal->set('form.competitors', [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]]);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    // Act
    $modal->call('checkHistory');

    // Assert
    $modal->assertReturned(fn (array $history): bool => $history['available']);
})->with([MembershipRole::Owner, MembershipRole::Manager]);

it('returns the requested page of opposing pairs', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
    $wrestlers = Wrestler::factory()->count(4)->for($promotion, 'promotion')->create();
    $past = Event::factory()->for($promotion, 'promotion')->past()->create();
    EventMatch::factory()->for($past)->withCompetitors($wrestlers->all())->create(['match_type' => MatchType::BattleRoyal]);
    $modal = livewire(FormModal::class, ['eventId' => $event->id]);
    $modal->set('form.matchType', MatchType::BattleRoyal);
    $modal->set('form.competitors', [['wrestlers' => $wrestlers->modelKeys()]]);

    // Act
    $modal->call('checkHistory', 2);

    // Assert
    $modal->assertReturned(fn (array $history): bool => $history['page'] === 2
        && $history['pages'] === 2
        && $history['totalNotices'] === 6
        && count($history['notices']) === 1
        && $history['notices'][0]['sourceUrl'] === route('events.show', $past));
});
