<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\PreviousStables;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->wrestler = Wrestler::factory()->create();
    actingAs(administrator());
});

describe('PreviousStablesTable Configuration', function () {
    it('requires wrestler id to be set', function (): void {
        // Act & Assert
        expect(fn () => (new PreviousStables())->builder())
            ->toThrow(LogicException::class, 'A wrestler was not provided.');
    });

    it('can set wrestler id', function (): void {
        // Act
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSet('wrestlerId', $this->wrestler->id);
    });

    it('uses the stable membership table', function (): void {
        // Act
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSet('databaseTableName', 'stables_wrestlers');
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

describe('PreviousStablesTable Rendering', function () {
    it('renders the stable history search control', function (): void {
        // Act
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSeeHtml('placeholder="Search stables"');
    });

    it('renders when the wrestler has no previous stable memberships', function (): void {
        // Act
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('No records found.');
    });

    it('renders previous stable membership details', function (): void {
        // Arrange
        $formerStable = Stable::factory()->create(['name' => 'Historic Stable']);
        $formerStable->wrestlers()->attach($this->wrestler, [
            'joined_at' => Date::parse('2024-01-15'),
            'left_at' => Date::parse('2024-06-30'),
        ]);

        // Act
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Historic Stable')
            ->assertSee('2024-01-15')
            ->assertSee('2024-06-30');
    });
});

describe('PreviousStablesTable Authorization', function () {
    it('allows access to administrators', function (): void {
        // Act
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertSuccessful();
    });

    it('forbids users without access to the wrestler', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $component = livewire(PreviousStables::class, ['wrestlerId' => $this->wrestler->id]);

        // Assert
        $component->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
