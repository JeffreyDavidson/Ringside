<?php

declare(strict_types=1);

use App\Actions\Events\ReleaseEventPublicationAction;
use App\Actions\Events\SaveSponsorInstructionAction;
use App\Enums\Events\PublicSection;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Events\EventPublication;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Queries\Events\PublicEventSourceQuery;
use App\Queries\Events\SponsorInstructionQuery;
use App\Services\Promotions\PromotionContextService;
use App\ViewModels\EventPackViewModel;
use Illuminate\View\View;
use PHPUnit\Framework\Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('an owner opens the private sponsor checklist without creating data', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create(['name' => 'Harbor Showcase']);

    $user = basicUser();
    $promotion->users()
        ->attach($user, [
            'role' => MembershipRole::Owner,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);

    // Act
    $response = get(route('events.sponsors.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.sponsors')
        ->assertViewHas('event', $event)
        ->assertSee('Sponsor commitments')
        ->assertSee('Harbor Showcase');

    expect(EventSponsorCommitment::query()
        ->count())->toBe(0);
});

test('printed packs show deliberately reviewed sponsor instructions to members', function (string $mode): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $commitment = EventSponsorCommitment::factory()
        ->for($event)
        ->create([
            'instruction_label' => 'Entrance banner',
            'instruction' => 'Hang before doors',
            'instruction_owner' => 'Ring crew',
            'notes' => 'PRIVATE-NOTE-7Q',
            'agreement_reference' => 'PRIVATE-REF-7Q',
            'evidence_note' => 'PRIVATE-EVIDENCE-7Q',
        ]);
    $commitment->sponsor->update([
        'contact_name' => 'PRIVATE-CONTACT-7Q',
        'notes' => 'PRIVATE-TERMS-7Q',
    ]);

    actingAs(administrator());
    $pack = app(EventPackViewModel::class)->data($event);
    $instructionPreview = app(SponsorInstructionQuery::class)->preview($event, $commitment, $mode, null, null);
    app(SaveSponsorInstructionAction::class)->handle($event, $commitment->id, 1, 0, $pack['card']['fingerprint'], $mode, null, null, $instructionPreview['hash']);
    $user = basicUser();
    $promotion->users()
        ->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);

    // Act
    $response = get(route('events.pack.show', ['event' => $event, 'print' => 1]));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.pack-print')
        ->assertSee('Entrance banner')
        ->assertSee('Hang before doors')
        ->assertSee('Ring crew');
})->with(['setup instruction' => 'setup', 'segment instruction' => 'segment']);

test('members cannot expose event sponsor histories through reviewed instructions', function (string $mode): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $commitment = EventSponsorCommitment::factory()
        ->for($event)
        ->create([
            'instruction_label' => 'Entrance banner',
            'instruction' => 'Hang before doors',
            'instruction_owner' => 'Ring crew',
            'notes' => 'PRIVATE-NOTE-7Q',
            'agreement_reference' => 'PRIVATE-REF-7Q',
            'evidence_note' => 'PRIVATE-EVIDENCE-7Q',
        ]);
    $commitment->sponsor->update([
        'contact_name' => 'PRIVATE-CONTACT-7Q',
        'notes' => 'PRIVATE-TERMS-7Q',
    ]);

    actingAs(administrator());
    $pack = app(EventPackViewModel::class)->data($event);
    $instructionPreview = app(SponsorInstructionQuery::class)->preview($event, $commitment, $mode, null, null);
    app(SaveSponsorInstructionAction::class)->handle($event, $commitment->id, 1, 0, $pack['card']['fingerprint'], $mode, null, null, $instructionPreview['hash']);
    $user = basicUser();
    $promotion->users()
        ->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);

    // Act
    $response = get(route('events.sponsors.show', $event));

    // Assert
    $response
        ->assertForbidden();
})->with(['setup instruction' => 'setup', 'segment instruction' => 'segment']);

test('members cannot expose sponsor directory records through reviewed instructions', function (string $mode): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $commitment = EventSponsorCommitment::factory()
        ->for($event)
        ->create([
            'instruction_label' => 'Entrance banner',
            'instruction' => 'Hang before doors',
            'instruction_owner' => 'Ring crew',
            'notes' => 'PRIVATE-NOTE-7Q',
            'agreement_reference' => 'PRIVATE-REF-7Q',
            'evidence_note' => 'PRIVATE-EVIDENCE-7Q',
        ]);
    $commitment->sponsor->update([
        'contact_name' => 'PRIVATE-CONTACT-7Q',
        'notes' => 'PRIVATE-TERMS-7Q',
    ]);

    actingAs(administrator());
    $pack = app(EventPackViewModel::class)->data($event);
    $instructionPreview = app(SponsorInstructionQuery::class)->preview($event, $commitment, $mode, null, null);
    app(SaveSponsorInstructionAction::class)->handle($event, $commitment->id, 1, 0, $pack['card']['fingerprint'], $mode, null, null, $instructionPreview['hash']);
    $user = basicUser();
    $promotion->users()
        ->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);

    // Act
    $response = get(route('sponsors.show', $commitment->sponsor_id));

    // Assert
    $response
        ->assertForbidden();
})->with(['setup instruction' => 'setup', 'segment instruction' => 'segment']);

test('reviewed instructions cannot carry private sponsor terms into shared event outputs', function (string $mode, string $routeName, bool $print, string $view): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $commitment = EventSponsorCommitment::factory()
        ->for($event)
        ->create([
            'instruction_label' => 'Entrance banner',
            'instruction' => 'Hang before doors',
            'instruction_owner' => 'Ring crew',
            'notes' => 'PRIVATE-NOTE-7Q',
            'agreement_reference' => 'PRIVATE-REF-7Q',
            'evidence_note' => 'PRIVATE-EVIDENCE-7Q',
        ]);
    $commitment->sponsor->update([
        'contact_name' => 'PRIVATE-CONTACT-7Q',
        'notes' => 'PRIVATE-TERMS-7Q',
    ]);

    actingAs(administrator());
    $pack = app(EventPackViewModel::class)->data($event);
    $instructionPreview = app(SponsorInstructionQuery::class)->preview($event, $commitment, $mode, null, null);
    app(SaveSponsorInstructionAction::class)->handle($event, $commitment->id, 1, 0, $pack['card']['fingerprint'], $mode, null, null, $instructionPreview['hash']);
    $user = basicUser();
    $promotion->users()
        ->attach($user, [
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
        ]);

    actingAs($user);

    // Act
    $response = get(route($routeName, ['event' => $event, 'print' => $print]));

    // Assert
    $response
        ->assertOk()
        ->assertViewHas('event', $event)
        ->assertDontSee('PRIVATE-NOTE-7Q')
        ->assertDontSee('PRIVATE-REF-7Q')
        ->assertDontSee('PRIVATE-EVIDENCE-7Q')
        ->assertDontSee('PRIVATE-CONTACT-7Q')
        ->assertDontSee('PRIVATE-TERMS-7Q');

    Assert::assertInstanceOf(View::class, $response->original);
    expect($response->original->name())->toBe($view);
})->with(['setup instruction' => 'setup', 'segment instruction' => 'segment'])
    ->with(['pack' => ['events.pack.show', false, 'events.pack'], 'print' => ['events.pack.show', true, 'events.pack-print'], 'live' => ['events.live-show.show', false, 'events.live']]);

test('guests receive the intended sponsor-history response', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    // Act
    $response = get(route('events.sponsors.show', $event));

    // Assert
    $response
        ->assertRedirect(route('login'));
});

test('managers receive the intended sponsor-history response', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();

    actingAsPromotionMember($promotion, MembershipRole::Manager);

    // Act
    $response = get(route('events.sponsors.show', $event));

    // Assert
    $response
        ->assertOk();
});

test('foreign managers receive the intended sponsor-history response', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $other = Promotion::factory()
        ->create();

    actingAsPromotionMember($other, MembershipRole::Manager);

    // Act
    $response = get(route('events.sponsors.show', $event));

    // Assert
    $response
        ->assertNotFound();
});

test('archived event sponsor histories remain readable without new-commitment controls', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create();
    $event->delete();

    actingAsPromotionMember($promotion, MembershipRole::Manager);

    // Act
    $response = get(route('events.sponsors.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertViewIs('events.sponsors')
        ->assertViewHas('event', $event)
        ->assertSee('Event archived')
        ->assertDontSeeHtml('wire:click="newCommitment"');
});

test('the public card and results omit all sponsor and reviewed-instruction markers', function (string $mode, bool $isResults, string $heading): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $commitment = EventSponsorCommitment::factory()
        ->for($event)
        ->create([
            'sponsor_name' => 'SPONSOR-NAME-7Q',
            'description' => 'SPONSOR-DESCRIPTION-7Q',
            'responsible' => 'SPONSOR-RESPONSIBLE-7Q',
            'notes' => 'SPONSOR-NOTE-7Q',
            'agreement_reference' => 'SPONSOR-AGREEMENT-7Q',
            'evidence_note' => 'SPONSOR-EVIDENCE-7Q',
            'evidence_url' => 'https://example.test/SPONSOR-EVIDENCE-URL-7Q',
            'instruction_label' => 'PACK-LABEL-7Q',
            'instruction' => 'PACK-INSTRUCTION-7Q',
            'instruction_owner' => 'PACK-OWNER-7Q',
        ]);
    $commitment->sponsor->update([
        'name' => 'DIRECTORY-NAME-7Q',
        'contact_name' => 'SPONSOR-CONTACT-7Q',
        'contact_email' => 'SPONSOR-EMAIL-7Q@example.test',
        'contact_phone' => 'SPONSOR-PHONE-7Q',
        'notes' => 'SPONSOR-TERMS-7Q',
    ]);
    $pack = app(EventPackViewModel::class)->data($event);
    $instructionPreview = app(SponsorInstructionQuery::class)->preview($event, $commitment, $mode, null, null);
    app(SaveSponsorInstructionAction::class)->handle($event, $commitment->id, 1, 0, $pack['card']['fingerprint'], $mode, null, null, $instructionPreview['hash']);

    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceQuery::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();
    auth()->logout();
    app(PromotionContextService::class)->clear();

    // Act
    $response = get(publicShowUrl($publication, $isResults));

    // Assert
    $response
        ->assertOk()
        ->assertSee($heading)
        ->assertDontSee('SPONSOR-NAME-7Q')
        ->assertDontSee('SPONSOR-DESCRIPTION-7Q')
        ->assertDontSee('SPONSOR-RESPONSIBLE-7Q')
        ->assertDontSee('SPONSOR-NOTE-7Q')
        ->assertDontSee('SPONSOR-AGREEMENT-7Q')
        ->assertDontSee('SPONSOR-EVIDENCE-7Q')
        ->assertDontSee('SPONSOR-EVIDENCE-URL-7Q')
        ->assertDontSee('DIRECTORY-NAME-7Q')
        ->assertDontSee('SPONSOR-CONTACT-7Q')
        ->assertDontSee('SPONSOR-EMAIL-7Q')
        ->assertDontSee('SPONSOR-PHONE-7Q')
        ->assertDontSee('SPONSOR-TERMS-7Q')
        ->assertDontSee('PACK-LABEL-7Q')
        ->assertDontSee('PACK-INSTRUCTION-7Q')
        ->assertDontSee('PACK-OWNER-7Q');
})->with(['setup instruction' => 'setup', 'segment instruction' => 'segment'])
    ->with(['card' => [false, 'Harbor Night'], 'results' => [true, 'Selected results']]);

test('the private public-page preview omits all sponsor and instruction markers', function (string $mode): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $commitment = EventSponsorCommitment::factory()
        ->for($event)
        ->create([
            'sponsor_name' => 'SPONSOR-NAME-7Q',
            'description' => 'SPONSOR-DESCRIPTION-7Q',
            'responsible' => 'SPONSOR-RESPONSIBLE-7Q',
            'notes' => 'SPONSOR-NOTE-7Q',
            'agreement_reference' => 'SPONSOR-AGREEMENT-7Q',
            'evidence_note' => 'SPONSOR-EVIDENCE-7Q',
            'evidence_url' => 'https://example.test/SPONSOR-EVIDENCE-URL-7Q',
            'instruction_label' => 'PACK-LABEL-7Q',
            'instruction' => 'PACK-INSTRUCTION-7Q',
            'instruction_owner' => 'PACK-OWNER-7Q',
        ]);
    $commitment->sponsor->update([
        'name' => 'DIRECTORY-NAME-7Q',
        'contact_name' => 'SPONSOR-CONTACT-7Q',
        'contact_email' => 'SPONSOR-EMAIL-7Q@example.test',
        'contact_phone' => 'SPONSOR-PHONE-7Q',
        'notes' => 'SPONSOR-TERMS-7Q',
    ]);
    $pack = app(EventPackViewModel::class)->data($event);
    $instructionPreview = app(SponsorInstructionQuery::class)->preview($event, $commitment, $mode, null, null);
    app(SaveSponsorInstructionAction::class)->handle($event, $commitment->id, 1, 0, $pack['card']['fingerprint'], $mode, null, null, $instructionPreview['hash']);

    $publication = EventPublication::factory()
        ->for($event)
        ->create(['draft' => [
            'announcement' => 'scheduled', 'summary' => 'Welcome to Harbor Night.', 'notice' => null, 'address' => false,
            'doors_at' => null, 'doors_offset' => null, 'tickets' => null, 'poster' => null,
            'matches' => [['id' => $fixture['match']->id, 'type' => true, 'stipulation' => false, 'titles' => false]],
            'results' => [$fixture['match']->id],
        ]]);
    $source = app(PublicEventSourceQuery::class)->get($event);
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Card);
    $publication->refresh();
    app(ReleaseEventPublicationAction::class)->handle($event, $fixture['actor'], $publication->version, $source['fingerprint'], PublicSection::Results);
    $publication->refresh();

    // Act
    $response = get(route('events.public-pages.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Harbor Night')
        ->assertDontSee('SPONSOR-NAME-7Q')
        ->assertDontSee('SPONSOR-DESCRIPTION-7Q')
        ->assertDontSee('SPONSOR-RESPONSIBLE-7Q')
        ->assertDontSee('SPONSOR-NOTE-7Q')
        ->assertDontSee('SPONSOR-AGREEMENT-7Q')
        ->assertDontSee('SPONSOR-EVIDENCE-7Q')
        ->assertDontSee('SPONSOR-EVIDENCE-URL-7Q')
        ->assertDontSee('DIRECTORY-NAME-7Q')
        ->assertDontSee('SPONSOR-CONTACT-7Q')
        ->assertDontSee('SPONSOR-EMAIL-7Q')
        ->assertDontSee('SPONSOR-PHONE-7Q')
        ->assertDontSee('SPONSOR-TERMS-7Q')
        ->assertDontSee('PACK-LABEL-7Q')
        ->assertDontSee('PACK-INSTRUCTION-7Q')
        ->assertDontSee('PACK-OWNER-7Q');
})->with(['setup instruction' => 'setup', 'segment instruction' => 'segment']);

test('reviewed sponsor instructions remain visible in the private pack positive control', function (string $mode): void {
    // Arrange
    $fixture = publicEventFixture();
    $event = $fixture['event'];
    $commitment = EventSponsorCommitment::factory()
        ->for($event)
        ->create([
            'sponsor_name' => 'SPONSOR-NAME-7Q',
            'description' => 'SPONSOR-DESCRIPTION-7Q',
            'responsible' => 'SPONSOR-RESPONSIBLE-7Q',
            'notes' => 'SPONSOR-NOTE-7Q',
            'agreement_reference' => 'SPONSOR-AGREEMENT-7Q',
            'evidence_note' => 'SPONSOR-EVIDENCE-7Q',
            'evidence_url' => 'https://example.test/SPONSOR-EVIDENCE-URL-7Q',
            'instruction_label' => 'PACK-LABEL-7Q',
            'instruction' => 'PACK-INSTRUCTION-7Q',
            'instruction_owner' => 'PACK-OWNER-7Q',
        ]);
    $commitment->sponsor->update([
        'name' => 'DIRECTORY-NAME-7Q',
        'contact_name' => 'SPONSOR-CONTACT-7Q',
        'contact_email' => 'SPONSOR-EMAIL-7Q@example.test',
        'contact_phone' => 'SPONSOR-PHONE-7Q',
        'notes' => 'SPONSOR-TERMS-7Q',
    ]);
    $pack = app(EventPackViewModel::class)->data($event);
    $instructionPreview = app(SponsorInstructionQuery::class)->preview($event, $commitment, $mode, null, null);
    app(SaveSponsorInstructionAction::class)->handle($event, $commitment->id, 1, 0, $pack['card']['fingerprint'], $mode, null, null, $instructionPreview['hash']);

    // Act
    $response = get(route('events.pack.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertSee('PACK-INSTRUCTION-7Q');
})->with(['setup instruction' => 'setup', 'segment instruction' => 'segment']);
