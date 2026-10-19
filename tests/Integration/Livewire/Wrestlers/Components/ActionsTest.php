<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RetireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Wrestlers\Components\Actions;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('wrestler actions component', function (): void {
    test('it brings a wrestler who was employed when retired back as employed', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->employed()->create();
        resolve(RetireAction::class)->handle($wrestler, now()->subHours(2));

        actingAs(administrator());
        $component = livewire(Actions::class, ['wrestler' => $wrestler->refresh()]);

        // Act
        $component->call('unretire');

        // Assert
        $component->assertDispatched('wrestler-updated');
        expect($wrestler->refresh()->status)->toBe(EmploymentStatus::Employed);
    });

    test('it brings a wrestler who was released before retiring back as released', function (): void {
        // Arrange
        $wrestler = Wrestler::factory()->employed()->create();
        resolve(ReleaseAction::class)->handle($wrestler, now()->subHours(3));
        resolve(RetireAction::class)->handle($wrestler->refresh(), now()->subHours(2));

        actingAs(administrator());
        $component = livewire(Actions::class, ['wrestler' => $wrestler->refresh()]);

        // Act
        $component->call('unretire');

        // Assert
        $component->assertDispatched('wrestler-updated');
        expect($wrestler->refresh()->status)->toBe(EmploymentStatus::Released);
    });
});
