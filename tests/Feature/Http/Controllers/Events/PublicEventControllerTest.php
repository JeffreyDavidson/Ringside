<?php

declare(strict_types=1);
use App\Actions\Events\DeleteAction;
use App\Data\Events\PublicEventData;
use App\Enums\MatchFinish;
use App\Enums\Promotions\MembershipRole;
use App\Models\Events\EventPack;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('card releases omit outcomes and results need their own reviewed release', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    $publication = releasePublicPage($event);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertSee('Robin North vs Casey Shore')
        ->assertDontSee('Pinfall')
        ->assertDontSee('View results');

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response->assertNotFound();

    $before = DB::table('events_matches')->where('event_id', $event->id)->first();
    $publication = releasePublicPage($event, 'results');

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertSee('View results')
        ->assertDontSee('Pinfall');

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertSee('Robin North — Pinfall')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect(DB::table('events_matches')->where('event_id', $event->id)->first())->toEqual($before);
});

test('every match finish renders its recorded winning side or draw without changing results', function (MatchFinish $finish): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $match = $world['match'];
    $match->forceFill(['match_finish' => $finish, 'winning_side_id' => $finish->requiresWinningSide() ? $match->winning_side_id : null])->save();
    savePublicDraft($event);
    releasePublicPage($event);
    $publication = releasePublicPage($event, 'results');

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert

    $response->assertSee($finish->label());
    if ($finish->requiresWinningSide()) {
        $response->assertSee('Robin North — '.$finish->label());
    }
    expect(freshModel($match)->match_finish)->toBe($finish);
})->with(MatchFinish::cases());

test('draft changes do not overwrite released pages and card changes require review', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    $publication = releasePublicPage($event);
    savePublicDraft($event, ['summary' => 'New draft words']);
    $world['left']->update(['name' => 'Private new ring name']);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertSee('Welcome to Harbor Night.')
        ->assertDontSee('New draft words')
        ->assertSee('Robin North')
        ->assertDontSee('Private new ring name');

    // Act
    $response = get(route('events.public-pages.edit', $event));

    // Assert
    $response->assertSee('Source changed');

    releasePublicPage($event);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertSee('New draft words')
        ->assertSee('Private new ring name');
});

test('corrected and deleted result sources suppress outcomes until explicit review', function (string $change): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    releasePublicPage($event);
    $publication = releasePublicPage($event, 'results');
    if ($change === 'finish') {
        $world['match']->update(['match_finish' => MatchFinish::Submission]);
    }
    if ($change === 'match') {
        $world['match']->delete();
    }
    if ($change === 'competitor') {
        $world['left']->delete();
    }

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertSee('Results are being updated')
        ->assertDontSee('Pinfall');

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertDontSee('View results')
        ->assertSee('Robin North');

    // Act
    $response = get(route('events.public-pages.edit', $event));

    // Assert
    $response->assertSee('Source changed');
    if ($change === 'finish') {
        releasePublicPage($event, 'results');

        // Act
        $response = get(publicShowUrl($publication, true));

        // Assert
        $response
            ->assertSee('Submission')
            ->assertSee('Results corrected on');
    }
})->with(['finish', 'match', 'competitor']);

test('changed dates suppress obsolete facts and poster links without exposing the new schedule', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event, ['tickets' => 'https://tickets.example.com/harbor']);
    $publication = releasePublicPage($event);
    $event->update(['date' => now()->addDays(6)]);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response
        ->assertSee('Event details are being updated')
        ->assertDontSee('tickets.example.com')
        ->assertDontSee('Casey Shore')
        ->assertDontSee('Harbor Wrestling');
});

test('unpublishing results keeps the card and deleting restoring cannot restore publication', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    releasePublicPage($event);
    $publication = releasePublicPage($event, 'results');
    $editor = get(route('events.public-pages.edit', $event));
    // Act
    $response = delete(route('events.public-pages.unpublish', $event), ['complete' => 1, 'version' => $publication->version, 'fingerprint' => data_get($editor->viewData('source'), 'fingerprint'), 'section' => 'results']);

    // Assert
    $response->assertSessionHasNoErrors();

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response->assertNotFound();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response->assertOk();

    app(DeleteAction::class)->handle($event);
    $event->restore();

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response->assertNotFound();

    expect(freshModel($publication)->published_at)->toBeNull();
});

test('foreign signed in visitors read only the public release while private endpoints stay isolated', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    $publication = releasePublicPage($event);
    auth()->logout();
    session()->flush();
    $foreign = Promotion::factory()->create();
    actingAsPromotionMember($foreign, MembershipRole::Owner);

    // Act
    $response = get(publicShowUrl($publication));

    // Assert
    $response->assertSee('Robin North vs Casey Shore');

    // Act
    $response = get(route('events.public-pages.edit', $event));

    // Assert
    $response->assertNotFound();

    // Act
    $response = get(route('events.public-pages.show', $event));

    // Assert
    $response->assertNotFound();

    // Act
    $response = post(route('events.public-pages.publish', $event), ['complete' => 1, 'version' => $publication->version, 'fingerprint' => str_repeat('a', 64), 'reviewed' => 1, 'section' => 'card']);

    // Assert
    $response->assertNotFound();

    expect(freshModel($publication)->version)->toBe($publication->version);
});

test('all outward channels omit operational prose and unselected outcomes', function (): void {
    $world = publicEventFixture();
    $event = $world['event'];
    EventPack::factory()->for($event)->create(['content' => ['timezone' => 'UTC', 'doors_at' => null, 'revision' => null, 'master' => 'LEAK-MASTER-7Q', 'notes' => 'LEAK-FEE-1234.56', 'follow_up' => 'LEAK-TRAVEL-7Q', 'matches' => [], 'segments' => []]]);
    [$unselected, $opponent] = Wrestler::factory()
        ->for($event->promotion()->firstOrFail())
        ->bookable()
        ->count(2)
        ->sequence(['name' => 'UNSELECTED-7Q'], ['name' => 'Harbor Opponent'])
        ->create()
        ->all();
    EventMatch::factory()->for($event)->withCompetitors([$unselected, $opponent])->create(['match_number' => 9, 'match_finish' => MatchFinish::Submission, 'preview' => 'LEAK-STORY-7Q']);
    savePublicDraft($event);
    releasePublicPage($event);
    $publication = releasePublicPage($event, 'results');

    $surfaces = publicEventSurfaces($event, $publication);

    foreach ($surfaces as $surface => $text) {
        foreach (['LEAK-EVENT-7Q', 'LEAK-MATCH-7Q', 'LEAK-MASTER-7Q', 'LEAK-FEE-1234.56', 'LEAK-TRAVEL-7Q', 'UNSELECTED-7Q', 'LEAK-STORY-7Q', 'Submission'] as $marker) {
            expect($text)->not->toContain($marker, $surface.': '.$marker);
        }
    }
});

test('a draw never names a winner even if a historical side reference remains', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $world['match']->update(['match_finish' => MatchFinish::TimeLimitDraw]);
    savePublicDraft($event);
    releasePublicPage($event);
    $publication = releasePublicPage($event, 'results');

    // Act
    $response = get(publicShowUrl($publication, true));

    // Assert
    $response
        ->assertSee('Time Limit Draw')
        ->assertDontSee('Robin North — Time Limit Draw');
});

test('public card and result routes receive the intended released projection', function (bool $results): void {
    // Arrange
    $world = publicEventFixture();
    savePublicDraft($world['event']);
    releasePublicPage($world['event']);
    $publication = releasePublicPage($world['event'], 'results');
    auth()->logout();

    // Act
    $response = get(publicShowUrl($publication, $results));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('public-events.show')
        ->assertViewHas('page', fn (PublicEventData $page): bool => $page->title === 'Harbor Night' && $page->isResults === $results && ! $page->isPreview)
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Robots-Tag', $results ? 'noindex, nofollow' : 'index, follow');
})->with(['card' => false, 'results' => true]);
