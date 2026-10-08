<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousStables;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->wrestler = Wrestler::factory()->create();
    actingAs(administrator());
});

describe('PreviousStablesTable Configuration', function () {
    it('uses stable identifiers for rendered rows', function (): void {
        // Arrange
        $formerStable = Stable::factory()->create();
        $otherStable = Stable::factory()->create();
        $otherWrestler = Wrestler::factory()->create();
        $otherStable->wrestlers()->attach($otherWrestler, [
            'joined_at' => Date::parse('2023-01-01'),
            'left_at' => Date::parse('2023-06-01'),
        ]);
        $formerStable->wrestlers()->attach($this->wrestler, [
            'joined_at' => Date::parse('2024-01-01'),
            'left_at' => Date::parse('2024-06-01'),
        ]);

        // Act
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSeeHtml('wire:key="row-'.$formerStable->id.'"');
    });
});

describe('PreviousStablesTable Query Building', function () {
    it('returns the wrestler previous stable memberships', function (): void {
        // Arrange
        $formerStable = Stable::factory()->create();
        $formerStable->wrestlers()->attach($this->wrestler, [
            'joined_at' => Date::parse('2024-01-01'),
            'left_at' => Date::parse('2024-06-01'),
        ]);

        // Act
        $stables = tap(app(PreviousStables::class), function (PreviousStables $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($stables->modelKeys())->toBe([$formerStable->id]);
    });

    it('excludes previous memberships belonging to another wrestler', function (): void {
        // Arrange
        $otherWrestler = Wrestler::factory()->create();
        $otherStable = Stable::factory()->create();
        $otherStable->wrestlers()->attach($otherWrestler, [
            'joined_at' => Date::parse('2024-01-01'),
            'left_at' => Date::parse('2024-06-01'),
        ]);

        // Act
        $stables = tap(app(PreviousStables::class), function (PreviousStables $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($stables->modelKeys())->not->toContain($otherStable->id);
    });

    it('excludes current stable memberships', function (): void {
        // Arrange
        $currentStable = Stable::factory()->create();
        $currentStable->wrestlers()->attach($this->wrestler, [
            'joined_at' => Date::parse('2024-01-01'),
        ]);

        // Act
        $stables = tap(app(PreviousStables::class), function (PreviousStables $table): void {
            $table->wrestlerId = $this->wrestler->id;
        })->builder()->get();

        // Assert
        expect($stables->modelKeys())->not->toContain($currentStable->id);
    });
});
