<?php

declare(strict_types=1);

use App\Enums\MatchFinish;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Matches\Modals\FormModal;
use App\Livewire\Wrestlers\Tables\PreviousMatches;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('filters an opponent on another side without treating a partner as an opponent', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    [$subject, $opponent, $other] = Wrestler::factory()->count(3)->for($promotion, 'promotion')->create()->all();
    $event = Event::factory()->for($promotion, 'promotion')->past()->create();
    $opposed = EventMatch::factory()->for($event)->withCompetitors([$subject, $opponent])->create();
    $partnerMatch = EventMatch::factory()->for($event)->withCompetitors([$subject, $other])->create();
    MatchCompetitor::factory()->for($partnerMatch, 'eventMatch')->for($opponent, 'competitor')->create([
        'match_side_id' => $partnerMatch->sides()->firstOrFail()->id,
    ]);

    // Act
    $table = livewire(PreviousMatches::class, ['wrestlerId' => $subject->id, 'opponent' => 'wrestler:'.$opponent->id]);

    // Assert
    expect($table->instance()->builder()->pluck('events_matches.id')->all())->toBe([$opposed->id]);
    $table->assertSee('Only matches against '.$opponent->name);
});

it('fails closed for malformed and foreign opponent filters and can clear them', function (mixed $filter): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    [$subject, $other] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $event = Event::factory()->for($promotion, 'promotion')->past()->create();
    EventMatch::factory()->for($event)->withCompetitors([$subject, $other])->create();
    $table = livewire(PreviousMatches::class, ['wrestlerId' => $subject->id, 'opponent' => $filter]);

    // Act
    $records = $table->instance()->builder()->count();

    // Assert
    expect($records)->toBe(0);
    $table->assertSee('Opponent unavailable in this promotion.');

    // Act
    $table->call('clearFilters');

    // Assert
    $table->assertSet('opponent', '');
    expect($table->instance()->builder()->count())->toBe(1);
})->with(['array' => [['wrestler:1']], 'unknown type' => ['title:1'], 'malformed' => ['wrestler:1 OR 1=1'], 'missing' => ['wrestler:9999999999']]);

it('does not expose the name of a foreign opponent to a global administrator', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $subject = Wrestler::factory()->for($promotion, 'promotion')->create();
    $foreign = Wrestler::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'Private Foreign Opponent']);

    // Act
    $table = livewire(PreviousMatches::class, ['wrestlerId' => $subject->id, 'opponent' => 'wrestler:'.$foreign->id]);

    // Assert
    $table->assertDontSee('Private Foreign Opponent')->assertSee('Opponent unavailable in this promotion.');
});

it('shows unavailable history for an unowned profile instead of claiming zero history', function (): void {
    // Arrange
    actingAs(administrator());
    $subject = Wrestler::factory()->create();

    // Act
    $table = livewire(PreviousMatches::class, ['wrestlerId' => $subject->id]);

    // Assert
    $table->assertSee('History unavailable.')->assertDontSee('No previous matches yet.');
});

it('redacts foreign retained references and displays known outcomes safely', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    [$subject, $opponent] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $event = Event::factory()->for($promotion, 'promotion')->past()->create();
    $known = EventMatch::factory()->for($event)->withCompetitors([$subject, $opponent])->create();
    $known->update(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $known->sides()->firstOrFail()->id]);
    $foreign = Wrestler::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'Foreign History Secret']);
    EventMatch::factory()->for($event)->withCompetitors([$subject, $foreign])->create(['match_finish' => MatchFinish::NoDecision]);
    $opponent->delete();

    // Act
    $table = livewire(PreviousMatches::class, ['wrestlerId' => $subject->id]);

    // Assert
    $table->assertSee('Pinfall')->assertSee('Deleted')->assertSee('Record unavailable')
        ->assertSee('Result incomplete')->assertDontSee('Foreign History Secret')
        ->assertDontSeeHtml('href="'.route('wrestlers.show', $opponent).'"');
});

it('shows a neutral recorded result without inventing a winning side', function (MatchFinish $finish): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    [$subject, $opponent] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $event = Event::factory()->for($promotion, 'promotion')->past()->create();
    EventMatch::factory()->for($event)->withCompetitors([$subject, $opponent])->create(['match_finish' => $finish]);

    // Act
    $table = livewire(PreviousMatches::class, ['wrestlerId' => $subject->id]);

    // Assert
    $table->assertSee($finish->label())->assertDontSee('by '.$finish->label());
})->with([MatchFinish::TimeLimitDraw, MatchFinish::NoDecision]);

it('lets an active member read history while the booking action remains forbidden', function (): void {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    [$subject, $opponent] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $event = Event::factory()->for($promotion, 'promotion')->past()->create(['name' => 'Member History Event']);
    EventMatch::factory()->for($event)->withCompetitors([$subject, $opponent])->create(['match_finish' => MatchFinish::NoDecision]);
    $modal = livewire(FormModal::class, ['eventId' => $event->id]);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();

    // Act
    $table = livewire(PreviousMatches::class, ['wrestlerId' => $subject->id]);
    $modal->call('checkHistory');

    // Assert
    $table->assertSee('Member History Event');
    $modal->assertForbidden();
});
