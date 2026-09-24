<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('administrator can create a wrestler from the roster page', function (): void {
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $this->actingAs($administrator);

    $page = visit(route('wrestlers.index'));

    $page
        ->click('Add Wrestler')
        ->assertPresent('input[name="form.name"]')
        ->fill('input[name="form.name"]', 'Browser Test Wrestler')
        ->fill('input[name="form.hometown"]', 'Reno, Nevada')
        ->fill('input[name="form.height_feet"]', '6')
        ->fill('input[name="form.height_inches"]', '2')
        ->fill('input[name="form.weight"]', '245')
        ->press('Save')
        ->assertSee('Browser Test Wrestler');

    $createdWrestler = Wrestler::query()
        ->withoutGlobalScope('promotion_context')
        ->whereName('Browser Test Wrestler')
        ->firstOrFail();

    expect($createdWrestler->promotion_id)->toBe($promotion->id);

    $page
        ->click('button[aria-label="Actions for Browser Test Wrestler"]')
        ->click('[role="menuitem"]:has-text("Edit")')
        ->assertValue('input[name="form.name"]', 'Browser Test Wrestler')
        ->fill('input[name="form.name"]', 'Updated Browser Test Wrestler')
        ->press('Save')
        ->assertSee('Updated Browser Test Wrestler')
        ->assertNoJavascriptErrors();

    $wrestler = Wrestler::query()
        ->withoutGlobalScope('promotion_context')
        ->whereName('Updated Browser Test Wrestler')
        ->firstOrFail();

    expect($wrestler->promotion_id)->toBe($promotion->id);
});

test('administrator can create and edit a tag team from the roster page', function (): void {
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $firstWrestler = Wrestler::factory()->bookable()->create([
        'name' => 'Browser Team Member One',
        'promotion_id' => $promotion->id,
    ]);
    $secondWrestler = Wrestler::factory()->bookable()->create([
        'name' => 'Browser Team Member Two',
        'promotion_id' => $promotion->id,
    ]);
    $replacementWrestler = Wrestler::factory()->bookable()->create([
        'name' => 'Browser Team Replacement Member',
        'promotion_id' => $promotion->id,
    ]);
    $this->actingAs($administrator);

    $page = visit(route('tag-teams.index'));

    $page
        ->click('Add Tag Team')
        ->assertPresent('input[name="form.name"]')
        ->fill('input[name="form.name"]', 'Browser Test Tag Team')
        ->select('select[name="form.wrestlerA"]', (string) $firstWrestler->id)
        ->select('select[name="form.wrestlerB"]', (string) $secondWrestler->id)
        ->press('Save')
        ->assertSee('Browser Test Tag Team')
        ->click('button[aria-label="Actions for Browser Test Tag Team"]')
        ->click('[role="menuitem"]:has-text("Edit")')
        ->assertValue('input[name="form.name"]', 'Browser Test Tag Team')
        ->assertValue('select[name="form.wrestlerA"]', (string) $firstWrestler->id)
        ->assertValue('select[name="form.wrestlerB"]', (string) $secondWrestler->id)
        ->fill('input[name="form.name"]', 'Updated Browser Test Tag Team')
        ->select('select[name="form.wrestlerB"]', (string) $replacementWrestler->id)
        ->press('Save')
        ->assertSee('Updated Browser Test Tag Team')
        ->assertNoJavascriptErrors();

    $tagTeam = TagTeam::query()->whereName('Updated Browser Test Tag Team')->firstOrFail();

    expect($tagTeam->currentWrestlers->modelKeys())
        ->toContain($firstWrestler->id, $replacementWrestler->id)
        ->not->toContain($secondWrestler->id);
});

test('administrator can create and edit a venue from the venue directory', function (): void {
    $administrator = administrator();
    $this->actingAs($administrator);

    $page = visit(route('venues.index'));

    $page
        ->click('Add Venue')
        ->assertSee('Create Venue')
        ->fill('input[name="form.name"]', 'Browser Test Arena')
        ->fill('input[name="form.street_address"]', '100 Main Street')
        ->fill('input[name="form.city"]', 'Reno')
        ->fill('input[name="form.state"]', 'Nevada')
        ->fill('input[name="form.zipcode"]', '89501')
        ->press('Save')
        ->assertSee('Browser Test Arena')
        ->click('button[aria-label="Actions for Browser Test Arena"]')
        ->click('[role="menuitem"]:has-text("Edit")')
        ->assertValue('input[name="form.city"]', 'Reno')
        ->fill('input[name="form.city"]', 'Sparks')
        ->assertValue('input[name="form.city"]', 'Sparks')
        ->press('Save')
        ->assertSee('Sparks')
        ->assertNoJavascriptErrors();

    expect(Venue::query()->whereName('Browser Test Arena')->value('city'))->toBe('Sparks');
});
