<?php

namespace Database\Factories;

use App\Enums\TagTeamStatus;
use App\Models\Employment;
use App\Models\Retirement;
use App\Models\Suspension;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagTeamFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $modelClass = TagTeam::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => Str::title($this->faker->words(2, true)),
            'signature_move' => null,
            'status' => TagTeamStatus::unemployed(),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (TagTeam $tagTeam) {
            if ($tagTeam->wrestlers->isEmpty()) {
                $wrestlers = Wrestler::factory()->count(2)->create();
                foreach ($wrestlers as $wrestler) {
                    $tagTeam->wrestlers()->attach($wrestler->id, ['joined_at' => now()->toDateTimeString()]);
                }
            }
        });
    }

    public function bookable()
    {
        $start = now()->subDays(3);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::bookable()];
        })
        ->has(Employment::factory()->started($start))
        ->hasAttached(Wrestler::factory()->count(2)->has(Employment::factory()->started($start))->bookable(), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->currentWrestlers->each(function ($wrestler) {
                $wrestler->save();
            });
            $tagTeam->save();
        });
    }

    public function unbookable()
    {
        $start = Carbon::yesterday();

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::unbookable()];
        })
        ->has(Employment::factory()->started($start))
        ->hasAttached(Wrestler::factory()->count(2)->has(Employment::factory()->started($start))->injured(), ['joined_at' => Carbon::yesterday()])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }

    public function withFutureEmployment()
    {
        $start = Carbon::tomorrow();

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::future_employment()];
        })
        ->has(Employment::factory()->started($start))
        ->hasAttached(Wrestler::factory()->count(2)->has(Employment::factory()->started($start)), ['joined_at' => Carbon::now()])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }

    public function suspended()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::suspended()];
        })
        ->has(Employment::factory()->started($start))
        ->has(Suspension::factory()->started($end))
        ->hasAttached(Wrestler::factory()->count(2)->suspended(), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::retired()];
        })
        ->has(Employment::factory()->started($start)->ended($end))
        ->has(Retirement::factory()->started($end))
        ->hasAttached(Wrestler::factory()->count(2)->retired(), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }

    public function unemployed()
    {
        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::unemployed()];
        })
        ->hasAttached(Wrestler::factory()->count(2), ['joined_at' => Carbon::now()])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }

    public function released()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::released()];
        })
        ->has(Employment::factory()->started($start)->ended($end))
        ->hasAttached(Wrestler::factory()->count(2)->has(Employment::factory()->started($start)->ended($end)), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }

    public function withInjuredWrestler()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::UNbookable()];
        })
        ->has(Employment::factory()->started($start))
        ->hasAttached(Wrestler::factory()->injured()->has(Employment::factory()->started($start)), ['joined_at' => $start])
        ->hasAttached(Wrestler::factory()->has(Employment::factory()->started($start)), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }

    public function withSuspendedWrestler()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::UNbookable()];
        })
        ->has(Employment::factory()->started($start))
        ->hasAttached(Wrestler::factory()->suspended()->has(Employment::factory()->started($start)), ['joined_at' => $start])
        ->hasAttached(Wrestler::factory()->has(Employment::factory()->started($start)), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }

    public function softDeleted()
    {
        return $this->state(function (array $attributes) {
            return ['deleted_at' => now()];
        })
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }
}
