<?php

declare(strict_types=1);

namespace App\Models\Crew;

use App\Builders\Crew\CrewContactBuilder;
use App\Models\Concerns\BelongsToPromotion;
use Database\Factories\Crew\CrewContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $promotion_id
 * @property string $client_id
 * @property string $name
 * @property ?string $phone
 * @property ?string $email
 * @property ?string $notes
 * @property int $version
 * @property ?Carbon $archived_at
 */
#[Fillable(['name', 'phone', 'email', 'notes'])]
#[UseEloquentBuilder(CrewContactBuilder::class)]
#[UseFactory(CrewContactFactory::class)]
class CrewContact extends Model
{
    use BelongsToPromotion;

    /** @use HasFactory<CrewContactFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['version' => 'integer', 'archived_at' => 'datetime'];
    }
}
