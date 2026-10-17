<?php

declare(strict_types=1);

namespace Database\Factories\Roster\Availability;

use App\Models\Promotions\Promotion;
use App\Models\Roster\Availability\Unavailability;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Unavailability> */
class UnavailabilityFactory extends Factory
{
    protected $model = Unavailability::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'promotion_id' => fn (array $attributes): ?int => ($attributes['person_type'] === 'referee'
                ? Referee::query()->findOrFail($attributes['person_id'])
                : Wrestler::query()->findOrFail($attributes['person_id']))->promotion_id,
            'person_type' => (new Wrestler)->getMorphClass(),
            'person_id' => Wrestler::factory()->for(Promotion::factory()),
            'starts_on' => '2026-10-17',
            'ends_on' => '2026-10-17',
            'reason' => null,
        ];
    }
}
