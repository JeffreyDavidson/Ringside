<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Users\Role;
use App\Enums\Users\UserStatus;
use App\Livewire\Users\Tables\Main;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionInvitation;
use App\Models\Users\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('users table', function (): void {
    it('renders the configured table controls and user attributes', function (): void {
        // Arrange
        $administrator = User::factory()->administrator()->create([
            'first_name' => 'John',
            'last_name' => 'Admin',
            'email' => 'admin@example.com',
            'phone_number' => '1234567890',
            'status' => UserStatus::Active,
        ]);
        $basicUser = User::factory()->unverified()->create([
            'first_name' => 'Jane',
            'last_name' => 'Member',
            'email' => 'member@example.com',
        ]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Users')
            ->assertSee('Manage global accounts and platform access.')
            ->assertSeeHtml('data-test="index-page-header"')
            ->assertSeeHtml('data-test="table-metadata"')
            ->assertSeeHtml('data-test="table-toolbar"')
            ->assertSee('Add User')
            ->assertSeeHtml('placeholder="Search users"')
            ->assertSee('John Admin')
            ->assertSee($administrator->email)
            ->assertSee('(123) 456-7890')
            ->assertSee(Role::Administrator->name)
            ->assertSee('Jane Member')
            ->assertSee($basicUser->email)
            ->assertSee(Role::Basic->name)
            ->assertDontSee('Remove')
            ->assertDontSeeHtml('wire:click="delete(');
    });

    it('offers each user a labelled row actions menu', function (UserStatus $status, string $statusAction): void {
        // Arrange
        $user = User::factory()->create([
            'first_name' => 'Menu',
            'last_name' => 'Owner',
            'status' => $status,
        ]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSeeHtml('aria-label="Actions for Menu Owner"')
            ->assertSeeHtml('aria-label="User actions"')
            ->assertSeeHtml('href="'.route('users.show', $user).'"')
            ->assertSeeHtml("arguments: { modelId: {$user->id} }")
            ->assertSeeHtml("wire:click=\"changeStatus({$user->id}, '")
            ->assertSee($statusAction);
    })->with([
        'unverified' => [UserStatus::Unverified, 'Activate account'],
        'active' => [UserStatus::Active, 'Deactivate account'],
        'inactive' => [UserStatus::Inactive, 'Reactivate account'],
    ]);

    it('asks for confirmation before deactivating a user account', function (): void {
        // Arrange
        User::factory()->create([
            'first_name' => 'Menu',
            'last_name' => 'Owner',
            'status' => UserStatus::Active,
        ]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertSeeHtml('wire:confirm="Deactivate Menu Owner?"');
    });

    it('lets an administrator activate an unverified user account', function (): void {
        $user = User::factory()->unverified()->create();

        livewire(Main::class)
            ->call('changeStatus', $user->id, UserStatus::Active->value)
            ->assertHasNoErrors()
            ->assertDispatched('flash-message', type: 'status', message: 'User account status changed to Active.');

        expect($user->refresh()->status)->toBe(UserStatus::Active)
            ->and($user->email_verified_at)->toBeNull();
    });

    it('lets an administrator deactivate and reactivate user accounts', function (): void {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $component = livewire(Main::class);

        $component->call('changeStatus', $user->id, UserStatus::Inactive->value);

        expect($user->refresh()->status)->toBe(UserStatus::Inactive);

        $component->call('changeStatus', $user->id, UserStatus::Active->value);

        expect($user->refresh()->status)->toBe(UserStatus::Active);
    });

    it('recomputes the remembered status counts after a status change', function (): void {
        // Arrange
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $component = livewire(Main::class);

        // Act
        $component->call('changeStatus', $user->id, UserStatus::Inactive->value);

        // Assert
        expect($component->get('metadataSnapshot.statuses'))->toContain([
            'value' => UserStatus::Inactive->value,
            'label' => UserStatus::Inactive->label(),
            'count' => 1,
        ]);
    });

    it('authorizes before looking up the user', function (): void {
        $component = livewire(Main::class);
        actingAs(basicUser());

        $component
            ->call('changeStatus', PHP_INT_MAX, UserStatus::Inactive->value)
            ->assertForbidden();
    });

    it('does not let the last active administrator be deactivated', function (): void {
        $administrator = User::query()->where('role', Role::Administrator)->firstOrFail();

        livewire(Main::class)
            ->call('changeStatus', $administrator->id, UserStatus::Inactive->value)
            ->assertDispatched('flash-message', type: 'error', message: 'The platform must keep at least one active administrator. Make another user an active administrator first.');

        expect($administrator->refresh()->status)->toBe(UserStatus::Active);
    });

    it('lets an administrator be deactivated when another active administrator remains', function (): void {
        $other = User::factory()->administrator()->create(['status' => UserStatus::Active]);

        livewire(Main::class)
            ->call('changeStatus', $other->id, UserStatus::Inactive->value)
            ->assertDispatched('flash-message', type: 'status', message: 'User account status changed to Inactive.');

        expect($other->refresh()->status)->toBe(UserStatus::Inactive);
    });

    it('rejects invalid user status values', function (): void {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        livewire(Main::class)
            ->call('changeStatus', $user->id, 'suspended')
            ->assertHasErrors('status');

        expect($user->refresh()->status)->toBe(UserStatus::Active);
    });

    it('filters users by status', function (UserStatus $status): void {
        // Arrange
        User::factory()->create([
            'first_name' => 'Matching',
            'last_name' => 'Account',
            'status' => $status,
        ]);
        $hiddenStatus = $status === UserStatus::Active
            ? UserStatus::Inactive
            : UserStatus::Active;
        User::factory()->create([
            'first_name' => 'Hidden',
            'last_name' => 'Account',
            'status' => $hiddenStatus,
        ]);
        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.status', $status->value);

        // Assert
        $component
            ->assertSee('Matching Account')
            ->assertDontSee('Hidden Account');
    })->with(UserStatus::cases());

    it('searches users by email', function (): void {
        // Arrange
        $matchingUser = User::factory()->create([
            'first_name' => 'Unique',
            'last_name' => 'Account',
            'email' => 'unique@domain.com',
        ]);
        $hiddenUser = User::factory()->create([
            'first_name' => 'Different',
            'last_name' => 'Account',
            'email' => 'different@domain.com',
        ]);
        $component = livewire(Main::class);

        // Act
        $component->set('search', 'unique@');

        // Assert
        $component
            ->assertSee('Unique Account')
            ->assertSee($matchingUser->email)
            ->assertDontSee('Different Account')
            ->assertDontSee($hiddenUser->email);
    });

    it('orders users by last name', function (): void {
        // Arrange
        User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Cooper']);
        User::factory()->create(['first_name' => 'John', 'last_name' => 'Anderson']);
        User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Baker']);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertSeeInOrder([
            'John Anderson',
            'Jane Baker',
            'Bob Cooper',
        ]);
    });

    it('renders users without phone numbers', function (): void {
        // Arrange
        User::factory()->create([
            'first_name' => 'Missing',
            'last_name' => 'Phone',
            'phone_number' => null,
        ]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertSee('Missing Phone');
    });

    it('treats hostile search input as plain text', function (): void {
        // Arrange
        $user = User::factory()->create(['first_name' => 'Valid', 'last_name' => 'User']);
        $component = livewire(Main::class);

        // Act
        $component->set('search', "'; DROP TABLE users; --");

        // Assert
        $component->assertDontSee('Valid User');
        expect(User::query()->whereKey($user)->exists())->toBeTrue();
    });

    it('pages users with the same last name in a stable order by id', function (): void {
        // Arrange
        Auth::user()?->update(['last_name' => 'Zzz']);
        collect(range(11, 0))->each(fn (int $number) => User::factory()->create([
            'first_name' => sprintf('Member %02d', $number),
            'last_name' => 'Samename',
        ]));
        $component = livewire(Main::class);

        // Act
        $component->set('perPage', 5);

        // Assert
        $component
            ->assertSeeInOrder(['Member 11', 'Member 10', 'Member 09', 'Member 08', 'Member 07'])
            ->assertDontSee('Member 06');

        // Act
        $component->call('setPage', 2);

        // Assert
        $component
            ->assertSeeInOrder(['Member 06', 'Member 05', 'Member 04', 'Member 03', 'Member 02'])
            ->assertDontSee('Member 07')
            ->assertDontSee('Member 01');
    });

    it('breaks last name ties by id in the query', function (): void {
        // Arrange
        $component = livewire(Main::class);

        // Act
        $statements = recordStatements(fn () => $component->call('setPage', 1));

        // Assert
        $listing = collect($statements)->first(fn (array $statement): bool => str_contains($statement['sql'], 'order by "last_name" asc'));
        expect($listing)->not->toBeNull()
            ->and($listing['sql'] ?? '')->toContain('order by "last_name" asc, "id" asc');
    });
});

describe('account activation confirmation', function (): void {
    it('lists the pending invitations for the account email, excluding expired ones and other emails', function (): void {
        // Arrange
        $user = User::factory()->unverified()->create(['first_name' => 'Ivy', 'last_name' => 'Invited', 'email' => 'ivy@example.com']);
        PromotionInvitation::factory()->forEmail('Ivy@Example.com ')->withRole(MembershipRole::Owner)
            ->create(['promotion_id' => Promotion::factory()->create(['name' => 'Acme Wrestling'])]);
        PromotionInvitation::factory()->forEmail('ivy@example.com')->withRole(MembershipRole::Member)
            ->create(['promotion_id' => Promotion::factory()->create(['name' => 'Beta Pro'])]);
        PromotionInvitation::factory()->forEmail('ivy@example.com')->expired()
            ->create(['promotion_id' => Promotion::factory()->create(['name' => 'Expired Federation'])]);
        PromotionInvitation::factory()->forEmail('someone@example.com')
            ->create(['promotion_id' => Promotion::factory()->create(['name' => 'Other Alliance'])]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSeeHtml('wire:confirm="Activate Ivy Invited? This email has pending invitations: Acme Wrestling (Owner), Beta Pro (Member)."')
            ->assertDontSee('Expired Federation')
            ->assertDontSee('Other Alliance');
    });

    it('leaves the activation unchanged when the email has no pending invitations', function (): void {
        // Arrange
        User::factory()->unverified()->create(['email' => 'plain@example.com']);
        PromotionInvitation::factory()->forEmail('plain@example.com')->expired()->create();

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSee('Activate account')
            ->assertDontSeeHtml('wire:confirm="Activate');
    });

    it('does not add queries per row for a page of invited users', function (): void {
        // Arrange
        $countInvitationQueries = function (): int {
            $component = livewire(Main::class);

            return collect(recordStatements(fn () => $component->call('setPage', 1)))
                ->filter(fn (array $statement): bool => str_contains($statement['sql'], 'promotion_invitations'))
                ->count();
        };
        $invite = function (int $count): void {
            User::factory()->unverified()->count($count)->create()->each(
                fn (User $user) => PromotionInvitation::factory()->forEmail($user->email)->create(),
            );
        };
        $invite(1);
        $withOneInvitedUser = $countInvitationQueries();
        $invite(8);

        // Act
        $withManyInvitedUsers = $countInvitationQueries();

        // Assert
        expect($withOneInvitedUser)->toBeGreaterThan(0)
            ->and($withManyInvitedUsers)->toBe($withOneInvitedUser);
    });
});
