<?php

namespace Database\Factories;

use App\Enums\ManagerStatus;
use App\Models\Manager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManagerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected string $modelClass = Manager::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'status' => ManagerStatus::__default,
        ];
    }

    public function available(): self
    {
        return $this->state([
            'status' => ManagerStatus::AVAILABLE,
        ])->hasEmployments(1, ['started_at' => Carbon::yesterday()])
        ->afterCreating(function (Manager $manager) {
            $manager->save();
        });
    }

    public function withFutureEmployment(): self
    {
        return $this->state([
            'status' => ManagerStatus::FUTURE_EMPLOYMENT,
        ])->hasEmployments(1, ['started_at' => Carbon::tomorrow()])
        ->afterCreating(function (Manager $manager) {
            $manager->save();
        });
    }

    public function unemployed(): self
    {
        return $this->state([
            'status' => ManagerStatus::UNEMPLOYED,
        ])->afterCreating(function (Manager $manager) {
            $manager->save();
        });
    }

    public function retired(): self
    {
        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        return $this->state([
            'status' => ManagerStatus::RETIRED,
        ])->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
        ->hasRetirements(1, ['started_at' => $end])
        ->afterCreating(function (Manager $manager) {
            $manager->save();
        });
    }

    public function released(): self
    {
        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        return $this->state([
            'status' => ManagerStatus::RELEASED,
        ])->hasEmployments(1, ['started_at' => $start, 'ended_at' => $end])
        ->afterCreating(function (Manager $manager) {
            $manager->save();
        });
    }

    public function suspended(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state([
            'status' => ManagerStatus::SUSPENDED,
        ])->hasEmployments(1, ['started_at' => $start])
        ->hasSuspensions(1, ['started_at' => $end])
        ->afterCreating(function (Manager $manager) {
            $manager->save();
        });
    }

    public function injured(): self
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state([
            'status' => ManagerStatus::INJURED,
        ])->hasEmployments(1, ['started_at' => $start])
        ->hasInjuries(1, ['started_at' => $now])
        ->afterCreating(function (Manager $manager) {
            $manager->save();
        });
    }

    public function softDeleted(): self
    {
        return $this->state([
            'deleted_at' => now(),
        ])->afterCreating(function (Manager $manager) {
            $manager->save();
        });
    }
}
