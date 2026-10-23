<?php

declare(strict_types=1);

use App\Actions\Crew\SaveCrewContactAction;
use App\Data\Crew\CrewContactData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Livewire\Crew\Components\Directory;
use App\Models\Crew\CrewContact;
use App\Models\Promotions\Promotion;
use App\Services\Promotions\PromotionContextService;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

test('a manager creates edits and archives a contact in the directory', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    resolve(PromotionContextService::class)->set($promotion);
    resolve(PromotionContextService::class)->enforce();
    $page = livewire(Directory::class);

    // Act
    $page->call('newContact')->set('form.name', 'Alex Sound')->call('save');

    // Assert
    $page->assertHasNoErrors()->assertSee('Alex Sound');
    $contact = CrewContact::query()->sole();
    $page->call('edit', $contact->id)->set('form.name', 'Alex Operator')->call('save')->assertHasNoErrors();
    expect($contact->refresh()->name)->toBe('Alex Operator');
    $page->call('archive', $contact->id, $contact->version)->assertHasNoErrors();
    expect($contact->refresh()->archived_at)->not->toBeNull();
});

test('a membership downgrade refuses a directory save opened as manager', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $page = livewire(Directory::class)->call('newContact')->set('form.name', 'Cannot save');
    $promotion->users()->updateExistingPivot($user->id, ['role' => MembershipRole::Member]);
    $context->forgetMemberships();

    // Act
    $page->call('save');

    // Assert
    $page->assertForbidden();
    expect(CrewContact::query()->count())->toBe(0);
});

test('directory search treats wildcard characters literally and limits its results', function (): void {
    // Arrange
    actingAs(administrator());
    CrewContact::factory()->create(['name' => 'Alex%Sound']);
    CrewContact::factory()->create(['name' => 'Alex Other Sound']);

    // Act
    $page = livewire(Directory::class, ['search' => 'Alex%']);

    // Assert
    $page->assertSee('Alex%Sound')->assertDontSee('Alex Other Sound');
});

test('directory errors preserve an edit and cancel clears private draft fields', function (): void {
    // Arrange
    actingAs(administrator());
    $contact = CrewContact::factory()->create();
    $page = livewire(Directory::class)->call('edit', $contact->id)->set('form.name', 'My draft');
    resolve(SaveCrewContactAction::class)->handle(
        $contact,
        1,
        $contact->client_id,
        new CrewContactData('Other editor', null, null, null),
        signedInUser(),
    );

    // Act
    $page->call('save');

    // Assert
    $page->assertHasErrors('contact')->assertSet('form.name', 'My draft');
    $page->call('archive', $contact->id, 1)->assertHasErrors('contact');
    $page->call('cancel')->assertSet('editing', false)->assertSet('form.name', '')->assertSet('form.notes', '');
    expect($contact->refresh()->name)->toBe('Other editor');
});

test('a stale contact save and archive show the shared changed-by-someone-else message', function (): void {
    // Arrange
    actingAs(administrator());
    $contact = CrewContact::factory()->create();
    $page = livewire(Directory::class)->call('edit', $contact->id)->set('form.name', 'My draft');
    resolve(SaveCrewContactAction::class)->handle(
        $contact,
        1,
        $contact->client_id,
        new CrewContactData('Other editor', null, null, null),
        signedInUser(),
    );

    // Act
    $page->call('save');

    // Assert
    $page->assertHasErrors(['contact' => 'This was changed by someone else. Reload to see the latest version, review it, then try again. Your changes are still here.']);

    // Act
    $page->call('archive', $contact->id, 1);

    // Assert
    $page->assertHasErrors(['contact' => 'This was changed by someone else. Reload to see the latest version, review it, then try again.']);
});

test('an administrator must select a promotion before creating owned crew contacts', function (): void {
    // Arrange
    actingAs(administrator());
    resolve(PromotionContextService::class)->clear();

    // Act
    $page = livewire(Directory::class)->call('newContact');

    // Assert
    $page->assertForbidden();
    expect(CrewContact::query()->count())->toBe(0);
});

test('a member forging the directory component is forbidden', function (): void {
    // Arrange
    require_once __DIR__.'/../../../../Fixtures/production.php';
    $fixture = productionFixture();
    $event = $fixture['event'];
    $user = basicUser();
    Promotion::query()->findOrFail($event->promotion_id)->users()->attach($user, ['role' => MembershipRole::Member, 'status' => MembershipStatus::Active]);
    actingAs($user);
    get(route('events.pack.show', $event))->assertOk();

    // Act
    $component = livewire(Directory::class);

    // Assert
    $component->assertForbidden();
});

test('an owner or manager archives a contact and keeps its private details', function (MembershipRole $role): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $contact = CrewContact::factory()->for($promotion)->create(['notes' => 'Retain this note']);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => $role, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $page = livewire(Directory::class);

    // Act
    $page->call('archive', $contact->id, 1);

    // Assert
    $page->assertHasNoErrors();
    expect($contact->refresh()->archived_at)->not->toBeNull()
        ->and($contact->notes)->toBe('Retain this note')
        ->and($contact->version)->toBe(2);
})->with(['owner' => [MembershipRole::Owner], 'manager' => [MembershipRole::Manager]]);

test('a membership downgrade refuses an archive opened as manager', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $contact = CrewContact::factory()->for($promotion)->create(['notes' => 'Retain this note']);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $page = livewire(Directory::class);
    $promotion->users()->updateExistingPivot($user->id, ['role' => MembershipRole::Member]);
    $context->forgetMemberships();

    // Act
    $page->call('archive', $contact->id, 1);

    // Assert
    $page->assertForbidden();
    expect($contact->refresh()->archived_at)->toBeNull()
        ->and($contact->version)->toBe(1);
});

test('a membership downgrade refuses an edit opened as manager', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $contact = CrewContact::factory()->for($promotion)->create(['name' => 'Alex Sound']);
    $user = basicUser();
    $promotion->users()->attach($user, ['role' => MembershipRole::Manager, 'status' => MembershipStatus::Active]);
    actingAs($user);
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
    $page = livewire(Directory::class)->call('edit', $contact->id)->set('form.name', 'Cannot save');
    $promotion->users()->updateExistingPivot($user->id, ['role' => MembershipRole::Member]);
    $context->forgetMemberships();

    // Act
    $page->call('save');

    // Assert
    $page->assertForbidden();
    expect($contact->refresh()->name)->toBe('Alex Sound')
        ->and($contact->version)->toBe(1);
});

test('the directory refuses invalid contact details without writing', function (array $input, string $field): void {
    // Arrange
    actingAs(administrator());
    $context = resolve(PromotionContextService::class);
    $context->set(Promotion::factory()->create());
    $context->enforce();
    $page = livewire(Directory::class)->call('newContact');

    // Act
    foreach ($input as $property => $value) {
        $page->set("form.{$property}", $value);
    }

    $page->call('save');

    // Assert
    $page->assertHasErrors($field);
    expect(CrewContact::query()->count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'form.name'],
    'overlong name' => [['name' => str_repeat('a', 161)], 'form.name'],
    'overlong phone' => [['name' => 'Alex', 'phone' => str_repeat('1', 81)], 'form.phone'],
    'bad email' => [['name' => 'Alex', 'email' => 'not-an-email'], 'form.email'],
    'overlong notes' => [['name' => 'Alex', 'notes' => str_repeat('a', 2001)], 'form.notes'],
]);

test('the directory refuses a save without a submission key', function (): void {
    // Arrange
    actingAs(administrator());
    $context = resolve(PromotionContextService::class);
    $context->set(Promotion::factory()->create());
    $context->enforce();
    $page = livewire(Directory::class);

    // Act
    $page->set('form.name', 'Alex Sound')->call('save');

    // Assert
    $page->assertHasErrors('form.clientId');
    expect(CrewContact::query()->count())->toBe(0);
});
