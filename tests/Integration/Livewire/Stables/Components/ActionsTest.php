<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Stables\StableLifecycleAction;
use App\Enums\Stables\StableStatus;
use App\Livewire\Stables\Components\Actions;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('stable actions component', function (): void {
    test('it renders with the stable mounted', function (): void {
        // Arrange
        $stable = Stable::factory()->create();

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Assert
        $component
            ->assertOk()
            ->assertViewIs('livewire.stables.components.actions');
        expect($component->get('stable'))->toEqual($stable);
    });

    test('it only shows the lifecycle buttons that fit the stable state', function (
        string $state,
        array $visible,
        array $hidden,
    ): void {
        // Arrange
        $stable = Stable::factory()->{$state}()->create();

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Assert
        foreach ($visible as $method) {
            $component->assertSeeHtml("wire:click=\"{$method}\"");
        }

        foreach ($hidden as $method) {
            $component->assertDontSeeHtml("wire:click=\"{$method}\"");
        }
    })->with([
        'unformed' => ['withNoMembers', [], ['establish', 'disband', 'retire', 'unretire']],
        'ready to establish' => ['withEmployedDefaultMembers', ['establish'], ['disband', 'retire', 'unretire']],
        'active' => ['active', ['disband', 'retire'], ['establish', 'unretire']],
        'disbanded' => ['inactive', ['retire'], ['establish', 'disband', 'unretire']],
        'retired' => ['retired', ['unretire'], ['establish', 'disband', 'retire']],
    ]);

    test('it runs the real action, refreshes the card and reports success', function (
        string $state,
        string $method,
        StableStatus $status,
        string $message,
    ): void {
        // Arrange
        $stable = Stable::factory()->{$state}()->create();

        actingAs(administrator());
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $component->call($method);

        // Assert
        $component
            ->assertDispatched('stable-updated')
            ->assertDispatched('flash-message', type: 'status', message: $message);
        expect($stable->refresh()->status)->toBe($status);
    })->with([
        'establish' => ['withEmployedDefaultMembers', 'establish', StableStatus::Active, 'Stable successfully established.'],
        'disband' => ['active', 'disband', StableStatus::Inactive, 'Stable successfully disbanded.'],
        'retire' => ['active', 'retire', StableStatus::Retired, 'Stable successfully retired.'],
        'unretire' => ['retired', 'unretire', StableStatus::Active, 'Stable successfully unretired.'],
    ]);

    test('it shows the buttons for the new state after a lifecycle action succeeds', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        actingAs(administrator());
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $component->call('disband');

        // Assert
        $component
            ->assertSeeHtml('wire:click="retire"')
            ->assertDontSeeHtml('wire:click="disband"');
    });

    test('it rejects an ineligible action with error feedback and no change', function (
        string $state,
        string $method,
        string $message,
    ): void {
        // Arrange
        $stable = Stable::factory()->{$state}()->create();
        $statusBefore = $stable->status;

        actingAs(administrator());
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $component->call($method);

        // Assert
        $component
            ->assertNotDispatched('stable-updated')
            ->assertDispatched(
                'flash-message',
                fn (string $event, array $params): bool => $params['type'] === 'error'
                    && str_contains($params['message'], $message),
            );
        expect($stable->refresh()->status)->toBe($statusBefore);
    })->with([
        'disband an unformed stable' => ['withNoMembers', 'disband', 'is not active and cannot be disbanded.'],
        'establish without enough members' => ['withNoMembers', 'establish', 'requires at least'],
        'retire an unformed stable' => ['withNoMembers', 'retire', 'is not currently active and cannot be retired.'],
        'unretire an active stable' => ['active', 'unretire', 'is not retired and cannot be unretired.'],
    ]);

    test('it forbids lifecycle actions for unauthorized users without feedback', function (string $method): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        actingAs(basicUser());
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $component->call($method);

        // Assert
        $component->assertForbidden();
        $component
            ->assertNotDispatched('stable-updated')
            ->assertNotDispatched('flash-message');
        expect($stable->refresh()->status)->toBe(StableStatus::Active);
    })->with([
        'establish',
        'disband',
        'retire',
        'unretire',
    ]);

    test('it hides eligible lifecycle actions from users who are not authorized', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        actingAs(basicUser());

        // Act
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Assert
        expect($component->instance()->canPerform(StableLifecycleAction::Disband))->toBeFalse();
        $component->assertDontSeeHtml('wire:click="disband"');
    });

    test('it shows the merge, split and reunite buttons that fit the stable state', function (
        Closure $makeStable,
        array $visible,
        array $hidden,
    ): void {
        // Arrange
        $stable = $makeStable();

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Assert
        foreach ($visible as $method) {
            $component->assertSeeHtml("wire:click=\"{$method}\"");
        }

        foreach ($hidden as $method) {
            $component->assertDontSeeHtml("wire:click=\"{$method}\"");
        }
    })->with([
        'unformed' => [fn (): Stable => Stable::factory()->withNoMembers()->create(), [], ['merge', 'split', 'reunite']],
        'ready to establish' => [fn (): Stable => Stable::factory()->withEmployedDefaultMembers()->create(), [], ['merge', 'split', 'reunite']],
        'active with too few members to split' => [
            function (): Stable {
                $promotion = Promotion::factory()->create();
                Stable::factory()->active()->for($promotion, 'promotion')->create();

                return Stable::factory()->active()->for($promotion, 'promotion')->create();
            },
            ['merge'],
            ['split', 'reunite'],
        ],
        'active with no other stable to merge with' => [fn (): Stable => Stable::factory()->active()->create(), [], ['merge', 'split', 'reunite']],
        'active with enough members to split' => [
            function (): Stable {
                $promotion = Promotion::factory()->create();
                Stable::factory()->active()->for($promotion, 'promotion')->create();
                $stable = Stable::factory()->active()->for($promotion, 'promotion')->create();
                $stable->wrestlers()->attach(Wrestler::factory()->employed()->count(2)->create(), ['joined_at' => now()->subDay()]);

                return $stable;
            },
            ['merge', 'split'],
            ['reunite'],
        ],
        'disbanded' => [fn (): Stable => Stable::factory()->inactive()->create(), ['reunite'], ['merge', 'split']],
        'retired' => [fn (): Stable => Stable::factory()->retired()->create(), [], ['merge', 'split', 'reunite']],
    ]);

    test('it opens the modal for a restructuring action', function (
        string $method,
        string $state,
        string $component,
    ): void {
        // Arrange
        $stable = Stable::factory()->{$state}()->create();

        actingAs(administrator());
        $livewire = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $livewire->call($method);

        // Assert
        $livewire->assertDispatched(
            'openModal',
            fn (string $event, array $params): bool => $params === [$component, ['stableId' => $stable->id]],
        );
    })->with([
        'merge' => ['merge', 'active', 'stables.modals.merge-modal'],
        'split' => ['split', 'active', 'stables.modals.split-modal'],
        'reunite' => ['reunite', 'inactive', 'stables.modals.reunite-modal'],
    ]);

    test('it forbids opening a restructuring modal without the ability', function (string $method): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        actingAs(basicUser());
        $livewire = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $livewire->call($method);

        // Assert
        $livewire->assertNotDispatched('openModal');
        $livewire->assertForbidden();
    })->with([
        'merge',
        'split',
        'reunite',
    ]);

    test('it shows the reunite button when enough former members remain available', function (): void {
        // Arrange
        $stable = Stable::factory()->inactive()->create();
        $stable->wrestlers()->attach(Wrestler::factory()->employed()->count(2)->create(), [
            'joined_at' => now()->subDays(2),
            'left_at' => now()->subDay(),
        ]);
        $stable->previousWrestlers()->firstOrFail()->suspensions()->create(['started_at' => now()->subHour()]);

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Assert
        $component->assertSeeHtml('wire:click="reunite"');
    });

    test('it hides the reunite button when too few former members are available', function (): void {
        // Arrange
        $stable = Stable::factory()->inactive()->create();
        $stable->previousWrestlers()->get()->each(fn (Wrestler $wrestler) => $wrestler->retirements()->create(['started_at' => now()->subHour()]));
        $stable->previousTagTeams()->get()->each(fn ($tagTeam) => $tagTeam->retirements()->create(['started_at' => now()->subHour()]));

        actingAs(administrator());

        // Act
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Assert
        $component->assertDontSeeHtml('wire:click="reunite"');
    });

    test('it shows the restructuring buttons by promotion role', function (
        MembershipRole $role,
        bool $visible,
    ): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        $stable = Stable::factory()->active()->for($promotion, 'promotion')->create();
        Stable::factory()->active()->for($promotion, 'promotion')->create();
        $stable->wrestlers()->attach(Wrestler::factory()->employed()->count(2)->create(), ['joined_at' => now()->subDay()]);
        putStableMembersInPromotion($stable);

        actingAsPromotionMember($promotion, $role);

        // Act
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Assert
        expect($component->instance()->canPerform(StableLifecycleAction::Merge))->toBe($visible)
            ->and($component->instance()->canPerform(StableLifecycleAction::Split))->toBe($visible);
    })->with([
        'owner' => [MembershipRole::Owner, true],
        'manager' => [MembershipRole::Manager, true],
        'member' => [MembershipRole::Member, false],
    ]);

    test('it refreshes the page state after a modal restructures the stable', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        actingAs(administrator());
        $component = livewire(Actions::class, ['stable' => $stable]);
        $stable->currentActivityPeriod()->firstOrFail()->update(['ended_at' => now()]);

        // Act
        $component->dispatch('stable-restructured');

        // Assert
        $component
            ->assertDispatched('stable-updated')
            ->assertDispatched('refreshDatatable')
            ->assertDontSeeHtml('wire:click="merge"');
    });

    test('it refreshes the history tables after a lifecycle action', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        actingAs(administrator());
        $component = livewire(Actions::class, ['stable' => $stable]);

        // Act
        $component->call('disband');

        // Assert
        $component
            ->assertDispatched('stable-updated')
            ->assertDispatched('refreshDatatable');
    });
});
