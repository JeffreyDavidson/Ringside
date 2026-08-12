<?php

declare(strict_types=1);

namespace Database\Factories\Lifecycle;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Enums\Lifecycle\LifecycleTransitionType;
use App\Models\Lifecycle\LifecycleTransition;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LifecycleTransition>
 */
class LifecycleTransitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => 'title',
            'subject_id' => Title::factory(),
            'dimension' => LifecycleDimension::Activity,
            'transition' => LifecycleTransitionType::Debuted,
            'effective_at' => fake()->dateTimeBetween('-1 year'),
            'user_id' => null,
            'context' => null,
        ];
    }
}
