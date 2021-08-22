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
            'signature_move' => Str::title($this->faker->words(4, true)),
            'status' => TagTeamStatus::__default,
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
            return ['status' => TagTeamStatus::BOOKABLE];
        })
        ->has(Employment::factory()->started($start))
        ->hasAttached(Wrestler::factory()->count(2)->has(Employment::factory()->started($start))->bookable(), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->currentWrestlers->each->updateStatusAndSave();
            $tagTeam->updateStatusAndSave();
        });
    }

    public function unbookable()
    {
        $start = Carbon::yesterday();

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::UNBOOKABLE];
        })
        ->has(Employment::factory()->started($start))
        ->hasAttached(Wrestler::factory()->count(2)->has(Employment::factory()->started($start))->injured(), ['joined_at' => Carbon::yesterday()])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->updateStatusAndSave();
        });
    }

    public function withFutureEmployment()
    {
        $start = Carbon::tomorrow();

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::FUTURE_EMPLOYMENT];
        })
        ->has(Employment::factory()->started($start))
        ->hasAttached(Wrestler::factory()->count(2)->has(Employment::factory()->started($start)), ['joined_at' => Carbon::now()])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->updateStatusAndSave();
        });
    }

    public function suspended()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::SUSPENDED];
        })
        ->has(Employment::factory()->started($start))
        ->has(Suspension::factory()->started($end))
        ->hasAttached(Wrestler::factory()->count(2)->suspended(), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->updateStatusAndSave();
        });
    }

    public function retired()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::RETIRED];
        })
        ->has(Employment::factory()->started($start)->ended($end))
        ->has(Retirement::factory()->started($end))
        ->hasAttached(Wrestler::factory()->count(2)->retired(), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->updateStatusAndSave();
        });
    }

    public function unemployed()
    {
        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::UNEMPLOYED];
        })
        ->hasAttached(Wrestler::factory()->count(2), ['joined_at' => Carbon::now()])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->updateStatusAndSave();
        });
    }

    public function released()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::RELEASED];
        })
        ->has(Employment::factory()->started($start)->ended($end))
        ->hasAttached(Wrestler::factory()->count(2)->has(Employment::factory()->started($start)->ended($end)), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->updateStatusAndSave();
        });
    }

    public function withInjuredWrestler()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::UNBOOKABLE];
        })
        ->has(Employment::factory()->started($start))
        ->hasAttached(Wrestler::factory()->injured()->has(Employment::factory()->started($start)), ['joined_at' => $start])
        ->hasAttached(Wrestler::factory()->has(Employment::factory()->started($start)), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->updateStatusAndSave();
        });
    }

    public function withSuspendedWrestler()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(function (array $attributes) {
            return ['status' => TagTeamStatus::UNBOOKABLE];
        })
        ->has(Employment::factory()->started($start))
        ->hasAttached(Wrestler::factory()->suspended()->has(Employment::factory()->started($start)), ['joined_at' => $start])
        ->hasAttached(Wrestler::factory()->has(Employment::factory()->started($start)), ['joined_at' => $start])
        ->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->updateStatusAndSave();
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
