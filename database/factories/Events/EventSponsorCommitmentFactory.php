<?php

declare(strict_types=1);

namespace Database\Factories\Events;

use App\Enums\Events\SponsorCommitmentStatus;
use App\Enums\Events\SponsorCommitmentType;
use App\Models\Events\Event;
use App\Models\Events\EventSponsorCommitment;
use App\Models\Promotions\Promotion;
use App\Models\Sponsors\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EventSponsorCommitment> */
class EventSponsorCommitmentFactory extends Factory
{
    protected $model = EventSponsorCommitment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['event_id' => Event::factory()->for(Promotion::factory()),
            'promotion_id' => fn (array $attributes): ?int => Event::query()->withTrashed()->findOrFail($attributes['event_id'])->promotion_id,
            'sponsor_id' => fn (array $attributes): int => Sponsor::factory()->create(['promotion_id' => $attributes['promotion_id']])->id,
            'sponsor_name' => 'Harbor Hardware', 'submission_key' => (string) Str::uuid(), 'version' => 1,
            'type' => SponsorCommitmentType::Banner, 'status' => SponsorCommitmentStatus::Pending,
            'description' => 'Hang the entrance banner', 'timezone' => 'UTC', 'event_context' => []];
    }
}
