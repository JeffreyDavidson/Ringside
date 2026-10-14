<?php

declare(strict_types=1);

use App\Actions\Sponsors\SaveSponsorAction;
use App\Data\Sponsors\SponsorData;
use App\Livewire\Sponsors\Components\Directory;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('the directory saves a private reusable sponsor', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    actingAs(administrator());
    $component = livewire(Directory::class, ['promotionId' => $promotion->id]);
    $component->call('newSponsor');

    // Act
    $component->set('form.name', 'Harbor Hardware')->set('form.email', 'alex@example.test')->call('save');

    // Assert
    $component->assertHasNoErrors()->assertSee('Sponsor saved.');
    expect(Sponsor::query()->firstOrFail()->name)->toBe('Harbor Hardware');
});

test('editing and archiving a sponsor keeps private details and its event history', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $sponsor = Sponsor::factory()->create(['promotion_id' => $promotion->id, 'name' => 'Harbor Hardware', 'contact_name' => 'Alex', 'contact_email' => 'alex@example.test', 'contact_phone' => '555-0100', 'notes' => 'Private agreement']);
    $promise = EventSponsorCommitment::factory()->create(['promotion_id' => $promotion->id, 'sponsor_id' => $sponsor->id]);
    actingAs(administrator());
    $component = livewire(Directory::class, ['promotionId' => $promotion->id, 'sponsorId' => $sponsor->id]);

    // Act
    $component->call('edit', $sponsor->id)->set('form.archived', true)->call('save');

    // Assert
    $component->assertHasNoErrors()->assertSee('Sponsor saved.')->assertSee('Private agreement')->assertSee('Event promises for this sponsor');
    expect($sponsor->refresh()->archived_at)->not->toBeNull()->and($sponsor->contact_email)->toBe('alex@example.test');
    $component->call('edit', $sponsor->id)->set('form.archived', false)->call('save')->assertHasNoErrors();
    expect($sponsor->refresh()->archived_at)->toBeNull();
    $component->call('cancelDraft')->assertSet('formShown', false);
});

test('a stale directory form preserves its entered details', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $sponsor = Sponsor::factory()->create(['promotion_id' => $promotion->id]);
    actingAs(administrator());
    $component = livewire(Directory::class, ['promotionId' => $promotion->id]);
    $component->call('edit', $sponsor->id);
    app(SaveSponsorAction::class)->handle($promotion, $sponsor->id, 1, $sponsor->submission_key, new SponsorData('Renamed Harbor'));

    // Act
    $component->set('form.name', 'My draft')->call('save');

    // Assert
    $component->assertHasErrors('save')->assertSet('form.name', 'My draft');
    expect($sponsor->refresh()->name)->toBe('Renamed Harbor');
});
