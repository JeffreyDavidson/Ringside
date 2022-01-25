<?php

namespace Database\Factories;

use App\Models\Employment;
use App\Models\Manager;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmploymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $modelClass = Employment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $employable = $this->employable();

        return [
            'employable_id' => $employable::factory(),
            'employable_type' => $employable,
            'started_at' => now()->toDateTimeString(),
            'ended_at' => null,
        ];
    }

    /**
     * @param \Carbon\Carbon $employmentDate
     */
    public function started(Carbon $employmentDate)
    {
        return $this->state([
            'started_at' => $employmentDate->toDateTimeString() ?? now()->toDateTimeString(),
        ]);
    }

    /**
     * @param string|Carbon $releaseDate
     */
    public function ended($releaseDate = 'now')
    {
        return $this->state([
            'ended_at' => $releaseDate instanceof Carbon ? $releaseDate : new Carbon($releaseDate),
        ]);
    }

    public function employable()
    {
        return $this->faker->randomElement([
            Manager::class,
            Referee::class,
            TagTeam::class,
            Wrestler::class,
        ]);
    }
}
