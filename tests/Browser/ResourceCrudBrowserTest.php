<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
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

test('administrator can create and edit a manager from the roster page', function (): void {
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $this->actingAs($administrator);

    $page = visit(route('managers.index'));

    $page
        ->click('Add Manager')
        ->assertSee('Add Manager')
        ->fill('input[name="form.first_name"]', 'Browser Test')
        ->fill('input[name="form.last_name"]', 'Manager')
        ->press('Save')
        ->assertSee('Browser Test Manager')
        ->click('button[aria-label="Actions for Browser Test Manager"]')
        ->click('[role="menuitem"]:has-text("Edit")')
        ->assertValue('input[name="form.first_name"]', 'Browser Test')
        ->assertValue('input[name="form.last_name"]', 'Manager')
        ->fill('input[name="form.last_name"]', 'Representative')
        ->press('Save')
        ->assertSee('Browser Test Representative')
        ->assertNoJavascriptErrors();

    expect(Manager::query()->where('first_name', 'Browser Test')->value('last_name'))->toBe('Representative')
        ->and(Manager::query()->where('first_name', 'Browser Test')->value('promotion_id'))->toBe($promotion->id);
});

test('administrator can create and edit a referee from the roster page', function (): void {
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $this->actingAs($administrator);

    $page = visit(route('referees.index'));

    $page
        ->click('Add Referee')
        ->assertSee('Add Referee')
        ->fill('input[name="form.first_name"]', 'Browser Test')
        ->fill('input[name="form.last_name"]', 'Referee')
        ->press('Save')
        ->assertSee('Browser Test Referee');

    $page->click('button[aria-label="Actions for Browser Test Referee"]');
    $page->assertVisible('[role="menuitem"]:has-text("Edit")');
    $page->click('[role="menuitem"]:has-text("Edit")');
    $page->assertValue('input[name="form.first_name"]', 'Browser Test');
    $page
        ->assertValue('input[name="form.last_name"]', 'Referee')
        ->fill('input[name="form.last_name"]', 'Official')
        ->press('Save')
        ->assertSee('Browser Test Official')
        ->assertNoJavascriptErrors();

    expect(Referee::query()->where('first_name', 'Browser Test')->value('last_name'))->toBe('Official')
        ->and(Referee::query()->where('first_name', 'Browser Test')->value('promotion_id'))->toBe($promotion->id);
});

test('administrator can create and edit a stable from the roster page', function (): void {
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $this->actingAs($administrator);

    $page = visit(route('stables.index'));

    $page
        ->click('Add Stable')
        ->assertSee('Create Stable')
        ->fill('input[name="form.name"]', 'Browser Test Stable')
        ->press('Save')
        ->assertSee('Browser Test Stable')
        ->click('button[aria-label="Actions for Browser Test Stable"]')
        ->click('[role="menuitem"]:has-text("Edit")')
        ->assertSee('Edit Stable')
        ->assertValue('input[name="form.name"]', 'Browser Test Stable')
        ->fill('input[name="form.name"]', 'Updated Browser Test Stable')
        ->press('Save')
        ->assertSee('Updated Browser Test Stable')
        ->assertNoJavascriptErrors();

    expect(Stable::query()->whereName('Updated Browser Test Stable')->value('promotion_id'))->toBe($promotion->id);
});
