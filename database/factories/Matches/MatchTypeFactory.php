<?php

declare(strict_types=1);

namespace Database\Factories\Matches;

use App\Models\MatchType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchType>
 */
class MatchTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => str($name)->title()->value(),
            'slug' => str($name)->slug()->value(),
            'number_of_sides' => fake()->randomDigit(),
        ];
    }

    /**
     * Create a singles match type.
     */
    public function singles(): static
    {
        return $this->state([
            'name' => 'Singles',
            'slug' => 'singles',
            'number_of_sides' => 2,
        ]);
    }

    /**
     * Create a tag team match type.
     */
    public function tagTeam(): static
    {
        return $this->state([
            'name' => 'Tag Team',
            'slug' => 'tag-team',
            'number_of_sides' => 2,
        ]);
    }

    /**
     * Create a triple threat match type.
     */
    public function tripleThread(): static
    {
        return $this->state([
            'name' => 'Triple Threat',
            'slug' => 'triple-threat',
            'number_of_sides' => 3,
        ]);
    }

    /**
     * Create a triangle match type.
     */
    public function triangle(): static
    {
        return $this->state([
            'name' => 'Triangle',
            'slug' => 'triangle',
            'number_of_sides' => 3,
        ]);
    }

    /**
     * Create a fatal 4 way match type.
     */
    public function fatal4Way(): static
    {
        return $this->state([
            'name' => 'Fatal 4 Way',
            'slug' => 'fatal-4-way',
            'number_of_sides' => 4,
        ]);
    }

    /**
     * Create a 6 man tag team match type.
     */
    public function sixManTagTeam(): static
    {
        return $this->state([
            'name' => '6 Man Tag Team',
            'slug' => '6-man-tag-team',
            'number_of_sides' => 2,
        ]);
    }

    /**
     * Create an 8 man tag team match type.
     */
    public function eightManTagTeam(): static
    {
        return $this->state([
            'name' => '8 Man Tag Team',
            'slug' => '8-man-tag-team',
            'number_of_sides' => 2,
        ]);
    }

    /**
     * Create a 10 man tag team match type.
     */
    public function tenManTagTeam(): static
    {
        return $this->state([
            'name' => '10 Man Tag Team',
            'slug' => '10-man-tag-team',
            'number_of_sides' => 2,
        ]);
    }

    /**
     * Create a two on one handicap match type.
     */
    public function twoOnOneHandicap(): static
    {
        return $this->state([
            'name' => 'Two On One Handicap',
            'slug' => 'two-on-one-handicap',
            'number_of_sides' => 2,
        ]);
    }

    /**
     * Create a three on two handicap match type.
     */
    public function threeOnTwoHandicap(): static
    {
        return $this->state([
            'name' => 'Three On Two Handicap',
            'slug' => 'three-on-two-handicap',
            'number_of_sides' => 2,
        ]);
    }

    /**
     * Create a battle royal match type.
     */
    public function battleRoyal(): static
    {
        return $this->state([
            'name' => 'Battle Royal',
            'slug' => 'battle-royal',
            'number_of_sides' => null,
        ]);
    }

    /**
     * Create a royal rumble match type.
     */
    public function royalRumble(): static
    {
        return $this->state([
            'name' => 'Royal Rumble',
            'slug' => 'royal-rumble',
            'number_of_sides' => null,
        ]);
    }

    /**
     * Create a tornado tag team match type.
     */
    public function tornadoTagTeam(): static
    {
        return $this->state([
            'name' => 'Tornado Tag Team',
            'slug' => 'tornado-tag-team',
            'number_of_sides' => 2,
        ]);
    }

    /**
     * Create a gauntlet match type.
     */
    public function gauntlet(): static
    {
        return $this->state([
            'name' => 'Gauntlet',
            'slug' => 'gauntlet',
            'number_of_sides' => 2,
        ]);
    }
}
