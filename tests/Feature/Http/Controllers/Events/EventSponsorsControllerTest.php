<?php

declare(strict_types=1);

use App\Actions\Events\SaveSponsorInstructionAction;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Queries\Events\SponsorInstructionQuery;
use App\ViewModels\EventPackViewModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('an owner opens the private sponsor checklist without creating data', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create(['name' => 'Harbor Showcase']);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
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

    expect(EventSponsorCommitment::query()->count())->toBe(0);
});

test('the pack prints only deliberately reviewed sponsor instructions for members', function (string $mode): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create();
    $promise = EventSponsorCommitment::factory()->for($event)->create([
        'instruction_label' => 'Entrance banner', 'instruction' => 'Hang before doors', 'instruction_owner' => 'Ring crew',
        'notes' => 'PRIVATE-NOTE-7Q', 'agreement_reference' => 'PRIVATE-REF-7Q', 'evidence_note' => 'PRIVATE-EVIDENCE-7Q']);
    $promise->sponsor->update(['contact_name' => 'PRIVATE-CONTACT-7Q', 'notes' => 'PRIVATE-TERMS-7Q']);
    actingAs(administrator());
    $pack = app(EventPackViewModel::class)->data($event);
    $preview = app(SponsorInstructionQuery::class)->preview($event, $promise, $mode, null, null);
    app(SaveSponsorInstructionAction::class)->handle($event, $promise->id, 1, 0, $pack['card']['fingerprint'], $mode, null, null, $preview['hash']);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('events.pack.show', ['event' => $event, 'print' => 1]));
    $packResponse = get(route('events.pack.show', $event));
    $liveResponse = get(route('events.live-show.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Entrance banner')
        ->assertSee('Hang before doors')
        ->assertSee('Ring crew');
    foreach (['PRIVATE-NOTE-7Q', 'PRIVATE-REF-7Q', 'PRIVATE-EVIDENCE-7Q', 'PRIVATE-CONTACT-7Q', 'PRIVATE-TERMS-7Q'] as $marker) {
        $response->assertDontSee($marker);
        $packResponse
            ->assertOk()
            ->assertDontSee($marker);
        $liveResponse
            ->assertOk()
            ->assertDontSee($marker);
    }

    // Act
    $response = get(route('events.sponsors.show', $event));

    // Assert
    $response->assertForbidden();

    // Act
    $response = get(route('sponsors.show', $promise->sponsor_id));

    // Assert
    $response->assertForbidden();
})->with(['setup', 'segment']);

test('event sponsor histories refuse guests and foreign event identifiers', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $event = Event::factory()->for($promotion)->create();
    $foreign = Event::factory()->for(Promotion::factory())->create();

    // Act
    $response = get(route('events.sponsors.show', $event));

    // Assert
    $response->assertRedirect(route('login'));

    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $response = get(route('events.sponsors.show', $event));

    // Assert
    $response->assertOk();

    // Act
    $response = get(route('events.sponsors.show', $foreign));

    // Assert
    $response->assertNotFound();

    $event->delete();

    // Act
    $response = get(route('events.sponsors.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertSee('Event archived')
        ->assertDontSeeHtml('wire:click="newCommitment"');
});

test('sponsor records and reviewed pack instructions never reach public event pages', function (string $mode): void {
    // Arrange
    $world = publicEventFixture();
    $event = $world['event'];
    $promise = EventSponsorCommitment::factory()->for($event)->create([
        'sponsor_name' => 'SPONSOR-NAME-7Q', 'description' => 'SPONSOR-DESCRIPTION-7Q', 'responsible' => 'SPONSOR-RESPONSIBLE-7Q',
        'notes' => 'SPONSOR-NOTE-7Q', 'agreement_reference' => 'SPONSOR-AGREEMENT-7Q', 'evidence_note' => 'SPONSOR-EVIDENCE-7Q',
        'evidence_url' => 'https://example.test/SPONSOR-EVIDENCE-URL-7Q',
        'instruction_label' => 'PACK-LABEL-7Q', 'instruction' => 'PACK-INSTRUCTION-7Q', 'instruction_owner' => 'PACK-OWNER-7Q']);
    $promise->sponsor->update(['name' => 'DIRECTORY-NAME-7Q', 'contact_name' => 'SPONSOR-CONTACT-7Q',
        'contact_email' => 'SPONSOR-EMAIL-7Q@example.test', 'contact_phone' => 'SPONSOR-PHONE-7Q', 'notes' => 'SPONSOR-TERMS-7Q']);
    $pack = app(EventPackViewModel::class)->data($event);
    $preview = app(SponsorInstructionQuery::class)->preview($event, $promise, $mode, null, null);
    app(SaveSponsorInstructionAction::class)->handle($event, $promise->id, 1, 0, $pack['card']['fingerprint'], $mode, null, null, $preview['hash']);
    $response = get(route('events.pack.show', $event));

    // Assert
    $response
        ->assertOk()
        ->assertSee('PACK-INSTRUCTION-7Q');

    savePublicDraft($event);
    releasePublicPage($event);
    $publication = releasePublicPage($event, 'results');

    // Act
    $surfaces = publicEventSurfaces($event, $publication);

    // Assert
    foreach ($surfaces as $surface => $text) {
        foreach (['SPONSOR-NAME-7Q', 'SPONSOR-DESCRIPTION-7Q', 'SPONSOR-RESPONSIBLE-7Q', 'SPONSOR-NOTE-7Q',
            'SPONSOR-AGREEMENT-7Q', 'SPONSOR-EVIDENCE-7Q', 'SPONSOR-EVIDENCE-URL-7Q', 'DIRECTORY-NAME-7Q',
            'SPONSOR-CONTACT-7Q', 'SPONSOR-EMAIL-7Q', 'SPONSOR-PHONE-7Q', 'SPONSOR-TERMS-7Q',
            'PACK-LABEL-7Q', 'PACK-INSTRUCTION-7Q', 'PACK-OWNER-7Q'] as $marker) {
            expect($text)->not->toContain($marker, $surface.': '.$marker);
        }
    }
})->with(['setup', 'segment']);
