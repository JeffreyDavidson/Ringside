<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TagTeamStatus;
use App\Models\Employment;
use App\Models\Retirement;
use App\Models\Suspension;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagTeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => str($this->faker->words(2, true))->title(),
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

        $wrestlers = Wrestler::factory()
            ->has(Employment::factory()->started($start))
            ->bookable()
            ->count(2)
            ->create();

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::bookable()])
            ->has(Employment::factory()->started($start))
            ->hasAttached($wrestlers, ['joined_at' => $start])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }

    public function unbookable()
    {
        $start = Carbon::yesterday();

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::unbookable()])
            ->has(Employment::factory()->started($start))
            ->hasAttached(Wrestler::factory()->count(2)->has(Employment::factory()->started($start))->injured(), ['joined_at' => Carbon::yesterday()])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }

    public function withFutureEmployment()
    {
        $start = Carbon::tomorrow();

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::future_employment()])
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

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::suspended()])
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

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::retired()])
            ->has(Employment::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end))
            ->hasAttached(Wrestler::factory()->count(2)->retired(), ['joined_at' => $start])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }

    public function unemployed()
    {
        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::unemployed()])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }

    public function released()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);
        $wrestlers = Wrestler::factory()
            ->has(Employment::factory()->started($start)->ended($end))
            ->count(2)
            ->create();

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::released()])
            ->has(Employment::factory()->started($start)->ended($end))
            ->hasAttached($wrestlers, ['joined_at' => $start])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }

    public function withInjuredWrestler()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $wrestlerA = Wrestler::factory()->injured()->has(Employment::factory()->started($start));
        $wrestlerB = Wrestler::factory()->has(Employment::factory()->started($start));

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::unbookable()])
            ->has(Employment::factory()->started($start))
            ->hasAttached($wrestlerA, ['joined_at' => $start])
            ->hasAttached($wrestlerB, ['joined_at' => $start])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }

    public function withSuspendedWrestler()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $wrestlerA = Wrestler::factory()->suspended()->has(Employment::factory()->started($start));
        $wrestlerB = Wrestler::factory()->has(Employment::factory()->started($start));

        return $this->state(fn (array $attributes) => ['status' => TagTeamStatus::unbookable()])
            ->has(Employment::factory()->started($start))
            ->hasAttached($wrestlerA, ['joined_at' => $start])
            ->hasAttached($wrestlerB, ['joined_at' => $start])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }

    public function softDeleted()
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }

    public function withoutTagTeamPartners()
    {
        return $this->afterCreating(function (TagTeam $tagTeam) {
            $tagTeam->save();
        });
    }

    public function withTagTeamPartners()
    {
        return $this
            ->hasAttached(Wrestler::factory()->count(2)->unemployed(), ['joined_at' => now()->toDateTimeString()])
            ->afterCreating(function (TagTeam $tagTeam) {
                $tagTeam->save();
            });
    }
}
