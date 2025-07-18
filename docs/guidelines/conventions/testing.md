# Testing Conventions

## Test Structure

#### Standard Test Layout
```php
<?php

declare(strict_types=1);

use App\Models\Wrestlers\Wrestler;
use App\Actions\Wrestlers\EmployAction;

/**
 * Unit tests for Wrestler model structure and configuration.
 */
describe('Wrestler Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            // Arrange
            $wrestler = new Wrestler();

            // Act
            $fillable = $wrestler->getFillable();

            // Assert
            expect($fillable)->toEqual([
                'name',
                'hometown',
                'height_feet',
                'height_inches',
                'weight',
            ]);
        });
    });

    describe('trait integration', function () {
        test('uses required traits', function () {
            expect(Wrestler::class)->usesTrait(IsEmployable::class);
            expect(Wrestler::class)->usesTrait(IsRetirable::class);
        });
    });
});
```

#### Test Organization
- **Describe Blocks**: Use describe blocks for logical grouping
- **AAA Pattern**: Arrange-Act-Assert with clear separation
- **Descriptive Names**: Test names explain expected behavior
- **Consistent Structure**: Follow established patterns

## Factory Conventions

#### Factory Structure
```php
<?php

declare(strict_types=1);

namespace Database\Factories\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Wrestler instances.
 */
class WrestlerFactory extends Factory
{
    protected $model = Wrestler::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'hometown' => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'height_feet' => $this->faker->numberBetween(5, 7),
            'height_inches' => $this->faker->numberBetween(0, 11),
            'weight' => $this->faker->numberBetween(150, 350),
        ];
    }

    public function employed(): static
    {
        return $this->afterCreating(function (Wrestler $wrestler) {
            $wrestler->employments()->create([
                'started_at' => now()->subMonth(),
                'ended_at' => null,
            ]);
        });
    }

    public function retired(): static
    {
        return $this->afterCreating(function (Wrestler $wrestler) {
            $wrestler->retirements()->create([
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
        });
    }
}
```

#### Factory Organization
- **Realistic Data**: Use realistic data for business domain
- **State Methods**: Provide state methods for common scenarios
- **Relationship Handling**: Handle relationships appropriately
- **Consistent Patterns**: Follow established factory patterns
