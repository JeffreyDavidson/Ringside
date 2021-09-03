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
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'status' => ManagerStatus::__default,
        ];
    }

    public function employed()
    {
        return $this->state(function (array $attributes) {
            return ['status' => ManagerStatus::AVAILABLE];
        })
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->afterCreating(function (Manager $manager) {
            $manager->updateStatus()->save();
            $manager->load('employments');
        });
    }

    public function available()
    {
        return $this->state(function (array $attributes) {
            return ['status' => ManagerStatus::AVAILABLE];
        })
        ->has(Employment::factory()->started(Carbon::yesterday()))
        ->afterCreating(function (Manager $manager) {
            $manager->updateStatus()->save();
        });
    }

    public function withFutureEmployment()
    {
        return $this->state(function (array $attributes) {
            return ['status' => ManagerStatus::FUTURE_EMPLOYMENT];
        })
        ->has(Employment::factory()->started(Carbon::tomorrow()))
        ->afterCreating(function (Manager $manager) {
            $manager->updateStatus()->save();
        });
    }

    public function unemployed()
    {
        return $this->state(function (array $attributes) {
            return ['status' => ManagerStatus::UNEMPLOYED];
        })
        ->afterCreating(function (Manager $manager) {
            $manager->updateStatus()->save();
        });
    }

    public function retired()
    {
        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        return $this->state(function (array $attributes) {
            return ['status' => ManagerStatus::RETIRED];
        })
        ->has(Employment::factory()->started($start)->ended($end))
        ->has(Retirement::factory()->started($end))
        ->afterCreating(function (Manager $manager) {
            $manager->updateStatus()->save();
        });
    }

    public function released()
    {
        $start = now()->subMonths(1);
        $end = now()->subDays(3);

        return $this->state(function (array $attributes) {
            return ['status' => ManagerStatus::RELEASED];
        })
        ->has(Employment::factory()->started($start)->ended($end))
        ->afterCreating(function (Manager $manager) {
            $manager->updateStatus()->save();
        });
    }

    public function suspended()
    {
        $now = now();
        $start = $now->copy()->subDays(2);
        $end = $now->copy()->subDays(1);

        return $this->state(function (array $attributes) {
            return ['status' => ManagerStatus::SUSPENDED];
        })
        ->has(Employment::factory()->started($start))
        ->has(Suspension::factory()->started($end))
        ->afterCreating(function (Manager $manager) {
            $manager->updateStatus()->save();
        });
    }

    public function injured()
    {
        $now = now();
        $start = $now->copy()->subDays(2);

        return $this->state(function (array $attributes) {
            return ['status' => ManagerStatus::INJURED];
        })
        ->has(Employment::factory()->started($start))
        ->has(Injury::factory()->started($now))
        ->afterCreating(function (Manager $manager) {
            $manager->updateStatus()->save();
        });
    }

    public function softDeleted()
    {
        return $this->state(function (array $attributes) {
            return ['deleted_at' => now()];
        })
        ->afterCreating(function (Manager $manager) {
            $manager->updateStatus()->save();
        });
    }
}
