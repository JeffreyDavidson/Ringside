<?php

declare(strict_types=1);

namespace App\Models\Sponsors;

use App\Models\Concerns\BelongsToPromotion;
use App\Models\Events\EventSponsorCommitment;
use Database\Factories\Sponsors\SponsorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promotion_id
 * @property int $version
 * @property string $submission_key
 * @property string $name
 * @property ?string $contact_name
 * @property ?string $contact_email
 * @property ?string $contact_phone
 * @property ?string $notes
 * @property ?Carbon $archived_at
 */
#[Fillable('name', 'contact_name', 'contact_email', 'contact_phone', 'notes')]
#[UseFactory(SponsorFactory::class)]
class Sponsor extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<SponsorFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['version' => 'integer', 'archived_at' => 'datetime'];
    }

    /** @return HasMany<EventSponsorCommitment, $this> */
    public function commitments(): HasMany
    {
        return $this->hasMany(EventSponsorCommitment::class);
    }
}
