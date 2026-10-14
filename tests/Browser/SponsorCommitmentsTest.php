<?php

declare(strict_types=1);

use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;

use function Pest\Laravel\actingAs;

test('an operator reviews a promise on a phone and prints only approved setup wording', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create(['name' => 'Harbor Wrestling']);
    $event = Event::factory()->for($promotion)->create(['name' => 'Harbor Showcase']);
    $sponsor = Sponsor::factory()->create(['promotion_id' => $promotion->id, 'name' => 'Harbor Hardware', 'notes' => 'PRIVATE-TERMS-7Q']);
    $user = basicUser();
    $user->update(['first_name' => 'Morgan', 'last_name' => 'Ellis', 'email' => 'operator@example.test']);
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);

    // Act
    $page = visit(route('events.sponsors.show', $event));
    $page->resize(390, 844)->assertSee('Sponsor commitments')->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->assertScript('document.querySelector("select[name=filter]").options.length === 4')->click('Add promise')->type('Search sponsors', 'Harbor')->click('[data-sponsor-choice="'.$sponsor->id.'"]')->assertSee('Selected sponsor: Harbor Hardware')
        ->type('Private promise description', 'Entrance banner')->type('Responsible person', 'Alex')
        ->click('[data-sponsor-wording]')->type('Pack item label', 'Entrance banner')
        ->type('Reviewed operational instruction', 'Hang the banner before doors. Keep the entrance clear.')
        ->type('input[name="form.instructionOwner"]', 'Ring crew')->click('Save promise')
        ->assertSee('Promise saved.')->click('Review pack instruction')->click('Preview instructions')
        ->assertSee('This is the exact wording to save.')->click('Save reviewed instructions to pack')
        ->assertSee('Reviewed instructions saved to the pack.')->screenshot(fullPage: false, filename: 'sponsor-commitments-phone')
        ->resize(1440, 1000)->screenshot(fullPage: false, filename: 'sponsor-commitments-desktop')
        ->resize(320, 800)->assertScript('document.documentElement.scrollWidth <= innerWidth');

    // Assert
    expect(EventSponsorCommitment::query()->count())->toBe(1);
    $page->script('window.dispatchEvent(new Event("offline"))');
    $page->assertSee('Tracking requires a connection.')->assertScript('Array.from(document.querySelectorAll("button")).find(b => b.textContent.trim() === "Add promise").disabled');
    $page->script('window.dispatchEvent(new Event("online"))');
    $page->script('function () { window.sponsorOriginalFetch = window.fetch; window.fetch = (...args) => { window.fetch = window.sponsorOriginalFetch; return Promise.reject(new TypeError("Simulated connection failure")); }; return true; }');
    $page->click('Add promise')->assertSee('Tracking requires a connection.');

    $page->click('Reload saved state')->assertSee('Sponsor commitments');
    $page->click('Edit promise')->select('select[name="form.status"]', 'completed')->type('Completion evidence note', 'Checked before doors')->assertSee('Leave blank to record completion now.')->screenshot(fullPage: true, filename: 'sponsor-completion-form')->click('Save promise')->assertSee('Promise saved.');
    $page->select('select[name=filter]', 'all')->assertSee('Completed');
    expect(EventSponsorCommitment::query()->firstOrFail()->status)->toBe(SponsorCommitmentStatus::Completed);
    $page->click('Event pack')->click('Preview saved pack')->assertSee('Sponsor setup')->assertSee('Ring crew')->assertDontSee('PRIVATE-TERMS-7Q')
        ->uncheck('[data-print-section="sponsor_setup"]')->assertScript('document.querySelector("[data-section=sponsor_setup]").hidden')
        ->check('[data-print-section="sponsor_setup"]')->resize(1100, 1000)->screenshot(fullPage: true, filename: 'sponsor-commitments-print')
        ->assertNoJavaScriptErrors();
});

test('an owner creates a reusable contact and archives it without losing its details', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create(['name' => 'Harbor Wrestling']);
    $user = basicUser();
    $user->update(['first_name' => 'Morgan', 'last_name' => 'Ellis', 'email' => 'operator@example.test']);
    $promotion->users()->attach($user, ['role' => MembershipRole::Owner, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $name = 'Harbor Hardware and Community Event Support Cooperative';

    // Act
    $page = visit(route('sponsors.index'));
    $page->resize(390, 844)->click('Add sponsor')->type('Sponsor name', $name)
        ->type('Contact name', 'Alex Morgan')->type('Contact email', 'sponsor@example.test')
        ->type('Private notes', 'PRIVATE-DIRECTORY-7Q')->click('Save sponsor')->assertSee('Sponsor saved.')
        ->click('Edit sponsor')->check('Archive this sponsor')->click('Save sponsor')->assertSee('Archived')
        ->resize(320, 800)->assertScript('document.documentElement.scrollWidth <= innerWidth')
        ->resize(390, 844)->screenshot(fullPage: true, filename: 'sponsor-directory-phone')
        ->click($name)->assertSee('sponsor@example.test')->assertSee('PRIVATE-DIRECTORY-7Q')
        ->resize(1440, 1000)->screenshot(fullPage: false, filename: 'sponsor-directory-desktop')
        ->assertNoJavaScriptErrors();

    // Assert
    $sponsor = Sponsor::query()->firstOrFail();
    expect($sponsor->archived_at)->not->toBeNull()->and($sponsor->contact_email)->toBe('sponsor@example.test');
});
