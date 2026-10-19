<?php

declare(strict_types=1);

use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\RetireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Referees\Components\Actions;
use App\Models\Roster\Referees\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('referee actions component', function (): void {
    test('it brings a referee who was employed when retired back as employed', function (): void {
        // Arrange
        $referee = Referee::factory()->employed()->create();
        resolve(RetireAction::class)->handle($referee, now()->subHours(2));

        actingAs(administrator());
        $component = livewire(Actions::class, ['referee' => $referee->refresh()]);

        // Act
        $component->call('unretire');

        // Assert
        $component->assertDispatched('referee-updated');
        expect($referee->refresh()->status)->toBe(EmploymentStatus::Employed);
    });

    test('it brings a referee who was released before retiring back as released', function (): void {
        // Arrange
        $referee = Referee::factory()->employed()->create();
        resolve(ReleaseAction::class)->handle($referee, now()->subHours(3));
        resolve(RetireAction::class)->handle($referee->refresh(), now()->subHours(2));

        actingAs(administrator());
        $component = livewire(Actions::class, ['referee' => $referee->refresh()]);

        // Act
        $component->call('unretire');

        // Assert
        $component->assertDispatched('referee-updated');
        expect($referee->refresh()->status)->toBe(EmploymentStatus::Released);
    });
});
