<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Shared\UnitedStatesState;
use App\Livewire\Events\Modals\FormModal;
use App\Livewire\Referees\Modals\FormModal as RefereeFormModal;
use App\Models\Events\Venue;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use Tests\Integration\Livewire\Base\StubFormModal;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('modal lifecycle', function (): void {
    it('opens the modal', function (): void {
        // Arrange
        $modal = livewire(FormModal::class)
            ->assertSet('isModalOpen', false);

        // Act
        $modal->call('openModal');

        // Assert
        $modal->assertSet('isModalOpen', true);
    });

    it('closes the modal', function (): void {
        // Arrange
        $modal = livewire(FormModal::class)
            ->call('openModal')
            ->assertSet('isModalOpen', true);

        // Act
        $modal->call('closeModal');

        // Assert
        $modal->assertSet('isModalOpen', false);
    });
});

it('completes the shared form submission workflow', function (): void {
    // Arrange
    $modal = livewire(FormModal::class);
    $modal
        ->call('openModal')
        ->set('form.name', 'Shared Modal Event')
        ->set('form.venue_id', null);

    // Act
    $modal->call('save');

    // Assert
    $modal
        ->assertHasNoErrors()
        ->assertSet('isModalOpen', false)
        ->assertDispatched('refreshDatatable')
        ->assertDispatched('closeModal')
        ->assertDispatched('form-submitted');

    $this->assertDatabaseHas('events', [
        'name' => 'Shared Modal Event',
    ]);
});

describe('re-authorization when the form is saved', function (): void {
    it('refuses to save an edit after the manager was :dataset since opening the form', function (MembershipRole $role, MembershipStatus $status): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $referee = Referee::factory()->for($promotion, 'promotion')->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
        $manager = actingAsPromotionMember($promotion, MembershipRole::Manager);
        $modal = livewire(RefereeFormModal::class)
            ->call('openModal', $referee->id)
            ->set('form.first_name', 'Dave');
        changePromotionMembership($promotion, $manager, $role, $status);

        // Act
        $modal->call('save');

        // Assert
        $modal->assertForbidden();

        expect($referee->refresh()->first_name)->toBe('Earl');
    })->with('membership changes that revoke management');

    it('refuses to create a record after the manager was :dataset since opening the form', function (MembershipRole $role, MembershipStatus $status): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $manager = actingAsPromotionMember($promotion, MembershipRole::Manager);
        $modal = livewire(RefereeFormModal::class)
            ->call('openModal')
            ->set('form.first_name', 'Dave')
            ->set('form.last_name', 'Hebner');
        changePromotionMembership($promotion, $manager, $role, $status);

        // Act
        $modal->call('save');

        // Assert
        $modal->assertForbidden();

        expect(Referee::query()->withoutGlobalScopes()->exists())->toBeFalse();
    })->with('membership changes that revoke management');
});

dataset('membership changes that revoke management', [
    'demoted to member' => [MembershipRole::Member, MembershipStatus::Active],
    'suspended' => [MembershipRole::Manager, MembershipStatus::Suspended],
]);

describe('inherited form hooks', function (): void {
    it('requires modals relying on the shared workflow to define createForm', function (): void {
        // Arrange
        $modal = livewire(StubFormModal::class)
            ->call('openModal')
            ->set('form.name', 'Stub Arena')
            ->set('form.street_address', '1 Main Street')
            ->set('form.city', 'Springfield')
            ->set('form.state', UnitedStatesState::Illinois->value)
            ->set('form.zipcode', '62701');

        // Act
        $save = fn () => $modal->call('save');

        // Assert
        expect($save)->toThrow(LogicException::class, 'A form modal must define createForm().');
    });

    it('requires modals relying on the shared workflow to define updateForm', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['zipcode' => '62701']);
        $modal = livewire(StubFormModal::class)
            ->call('openModal', $venue->getKey());

        // Act
        $save = fn () => $modal->call('save');

        // Assert
        expect($save)->toThrow(LogicException::class, 'A form modal must define updateForm().');
    });
});
