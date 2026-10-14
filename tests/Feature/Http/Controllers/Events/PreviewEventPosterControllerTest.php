<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Models\Promotions\Promotion;
use App\Queries\Events\PublicEventSourceQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;

beforeEach(fn () => Storage::fake('local'));

test('the private preview streams the draft poster without publishing it', function (): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $publication = savePublicDraft($event, ['poster' => UploadedFile::fake()->image('draft.png'), 'poster_alt' => 'Draft poster']);
    $key = publicPosterKey($publication);
    $bytes = Storage::disk('local')->get('event-posters/'.$key) ?? throw new RuntimeException('Expected poster bytes');

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response
        ->assertOk()
        ->assertStreamedContent($bytes)
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    expect(freshModel($publication)->published_at)->toBeNull();
});

test('a private preview cannot expose a missing poster', function (string $case): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    if ($case !== 'no draft') {
        $publication = savePublicDraft($event, $case === 'missing file' ? ['poster' => UploadedFile::fake()->image('draft.png'), 'poster_alt' => 'Draft poster'] : []);
        if ($case === 'missing file') {
            Storage::disk('local')->delete('event-posters/'.publicPosterKey($publication));
        }
    }

    // Act
    $response = get(route('events.public-pages.poster', $event));

    // Assert
    $response->assertNotFound();
})->with(['no draft', 'no poster', 'missing file']);

test('private poster refuses guests members and outsiders without changing publication', function (string $actor): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    savePublicDraft($event);
    $publication = releasePublicPage($event);
    $source = app(PublicEventSourceQuery::class)->get($event);
    auth()->logout();
    session()->flush();
    if ($actor !== 'guest') {
        $promotion = $actor === 'outsider' ? Promotion::factory()->create() : $event->promotion()->firstOrFail();
        actingAsPromotionMember($promotion, $actor === 'outsider' ? MembershipRole::Owner : MembershipRole::Member);
    }

    // Act
    $response = \Pest\Laravel\get(route('events.public-pages.poster', $event), ['version' => $publication->version, 'fingerprint' => $source['fingerprint'], 'section' => 'card', 'reviewed' => 1]);

    // Assert
    if ($actor === 'guest') {
        $response->assertRedirect(route('login'));
    } elseif ($actor === 'outsider') {
        $response->assertNotFound();
    } else {
        $response->assertForbidden();
    }
    expect(freshModel($publication)->getAttributes())->toBe($publication->getAttributes());
})->with(['guest', 'member', 'outsider']);
