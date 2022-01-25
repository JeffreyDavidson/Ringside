<?php

namespace Database\Factories;

use App\Enums\ManagerStatus;
use App\Models\Employment;
use App\Models\Injury;
use App\Models\Manager;
use App\Models\Retirement;
use App\Models\Suspension;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ManagerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $modelClass = Manager::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'status' => ManagerStatus::unemployed(),
        ];
    }

    public function available()
    {
        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::available()])
            ->has(Employment::factory()->started(Carbon::yesterday()))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function withFutureEmployment()
    {
        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::future_employment()])
            ->has(Employment::factory()->started(Carbon::tomorrow()))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function unemployed()
    {
        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::unemployed()])
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function retired()
    {
        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::retired()])
            ->has(Employment::factory()->started($start)->ended($end))
            ->has(Retirement::factory()->started($end))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function released()
    {
        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::released()])
            ->has(Employment::factory()->started($start)->ended($end))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function suspended()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::suspended()])
            ->has(Employment::factory()->started($start))
            ->has(Suspension::factory()->started($end))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function injured()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(fn (array $attributes) => ['status' => ManagerStatus::injured()])
            ->has(Employment::factory()->started($start))
            ->has(Injury::factory()->started($now))
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }

    public function softDeleted()
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()])
            ->afterCreating(function (Manager $manager) {
                $manager->save();
            });
    }
}
