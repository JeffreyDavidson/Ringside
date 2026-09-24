<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Titles\TitleType;
use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;

test('administrator can create a basic user with unverified status', function (): void {
    $this->actingAs(administrator());

    $page = visit(route('users.index'));

    $page
        ->click('Add User')
        ->assertSee('Create User')
        ->fill('input[name="form.first_name"]', 'Browser')
        ->fill('input[name="form.last_name"]', 'User')
        ->fill('input[name="form.email"]', 'browser.user@example.com')
        ->select('select[name="form.role"]', Role::Basic->value)
        ->fill('input[name="form.password"]', 'password123')
        ->fill('input[name="form.password_confirmation"]', 'password123')
        ->press('Save')
        ->assertSee('Browser User')
        ->assertSee(Role::Basic->name)
        ->assertSee(UserStatus::Unverified->label());

    $page->assertNoJavascriptErrors();

    $user = User::query()->where('email', 'browser.user@example.com')->firstOrFail();

    expect($user->role)->toBe(Role::Basic)
        ->and($user->status)->toBe(UserStatus::Unverified);
});

test('administrator can edit a user role without changing account status', function (): void {
    $user = User::factory()->basicUser()->create([
        'first_name' => 'Browser',
        'last_name' => 'User',
        'email' => 'browser.user@example.com',
        'status' => UserStatus::Unverified,
    ]);
    $this->actingAs(administrator());

    $page = visit(route('users.index'));

    $page
        ->click('tr:has-text("Browser User") button[x-ref="button"]')
        ->click('tr:has-text("Browser User") button:has-text("Edit")')
        ->assertSee('Edit User')
        ->assertValue('input[name="form.first_name"]', 'Browser')
        ->assertValue('input[name="form.last_name"]', 'User')
        ->assertValue('input[name="form.email"]', 'browser.user@example.com')
        ->assertValue('select[name="form.role"]', Role::Basic->value)
        ->assertValue('input[name="form.password"]', '')
        ->fill('input[name="form.last_name"]', 'Administrator')
        ->select('select[name="form.role"]', Role::Administrator->value)
        ->press('Save')
        ->assertSee('Browser Administrator')
        ->assertSee(Role::Administrator->name)
        ->assertSee(UserStatus::Unverified->label())
        ->assertNoJavascriptErrors();

    $user->refresh();

    expect($user->last_name)->toBe('Administrator')
        ->and($user->role)->toBe(Role::Administrator)
        ->and($user->status)->toBe(UserStatus::Unverified);
});

test('administrator can activate a user from the actions menu', function (): void {
    $user = User::factory()->unverified()->create([
        'first_name' => 'Pending',
        'last_name' => 'Account',
        'email' => 'pending.account@example.com',
    ]);
    $this->actingAs(administrator());

    $page = visit(route('users.index'));

    $page
        ->click('tr:has-text("Pending Account") button[x-ref="button"]')
        ->click('tr:has-text("Pending Account") button:has-text("Activate account")')
        ->assertSee(UserStatus::Active->label())
        ->assertPresent('tr:has-text("Pending Account"):has-text("Active")')
        ->assertNoJavascriptErrors();

    expect($user->refresh()->status)->toBe(UserStatus::Active)
        ->and($user->email_verified_at)->toBeNull();
});

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

    $page->click('Add Referee');
    $page->assertNoJavascriptErrors();
    $page->assertVisible('input[name="form.first_name"]');
    $page->wait(0.35);
    $page->assertSee('Add Referee');

    $page
        ->fill('input[name="form.first_name"]', 'Browser Test')
        ->assertValue('input[name="form.first_name"]', 'Browser Test')
        ->fill('input[name="form.last_name"]', 'Referee')
        ->assertValue('input[name="form.last_name"]', 'Referee');

    $page->press('Save');
    $page->assertSee('Browser Test Referee');

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

test('administrator can create and edit a title from the title directory', function (): void {
    $promotion = Promotion::factory()->create();
    $administrator = administrator();
    $promotion->users()->attach($administrator, [
        'role' => MembershipRole::Owner->value,
        'status' => MembershipStatus::Active->value,
    ]);
    $this->actingAs($administrator);

    $page = visit(route('titles.index'));

    $page
        ->click('Add Title')
        ->assertSee('Create Title')
        ->fill('input[name="form.name"]', 'Browser Test Championship Title')
        ->select('select[name="form.type"]', TitleType::Singles->value)
        ->press('Save')
        ->assertSee('Browser Test Championship Title')
        ->click('button[aria-label="Actions for Browser Test Championship Title"]')
        ->click('[role="menuitem"]:has-text("Edit")')
        ->assertSee('Edit Title')
        ->assertValue('input[name="form.name"]', 'Browser Test Championship Title')
        ->assertValue('select[name="form.type"]', TitleType::Singles->value)
        ->fill('input[name="form.name"]', 'Updated Browser Test Championship Title')
        ->select('select[name="form.type"]', TitleType::TagTeam->value)
        ->press('Save')
        ->assertSee('Updated Browser Test Championship Title')
        ->assertNoJavascriptErrors();

    $title = Title::query()->whereName('Updated Browser Test Championship Title')->firstOrFail();

    expect($title->type)->toBe(TitleType::TagTeam)
        ->and($title->promotion_id)->toBe($promotion->id);
});

test('platform administrator can create and edit a promotion from the directory', function (): void {
    $this->actingAs(administrator());

    $page = visit(route('promotions.index'));

    $page
        ->click('Create promotion')
        ->assertSee('Create Promotion')
        ->fill('input[name="form.name"]', 'Browser Test Promotion')
        ->fill('input[name="form.slug"]', 'browser-test-promotion')
        ->press('Save')
        ->assertSee('Browser Test Promotion')
        ->assertSee('browser-test-promotion')
        ->assertNoJavascriptErrors();

    $page
        ->click('Edit')
        ->assertSee('Edit Promotion')
        ->assertValue('input[name="form.name"]', 'Browser Test Promotion')
        ->assertValue('input[name="form.slug"]', 'browser-test-promotion')
        ->fill('input[name="form.name"]', 'Updated Browser Test Promotion')
        ->fill('input[name="form.slug"]', 'updated-browser-test-promotion')
        ->press('Save')
        ->assertSee('Updated Browser Test Promotion')
        ->assertSee('updated-browser-test-promotion')
        ->assertNoJavascriptErrors();

    $promotion = Promotion::query()->whereSlug('updated-browser-test-promotion')->firstOrFail();

    expect($promotion->name)->toBe('Updated Browser Test Promotion');
});
