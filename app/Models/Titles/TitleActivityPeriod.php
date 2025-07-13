<?php

declare(strict_types=1);

namespace App\Models\Titles;

use Database\Factories\Titles\TitleActivityPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $title_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Title|null $title
 *
 * @method static \Database\Factories\Titles\TitleActivityPeriodFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleActivityPeriod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleActivityPeriod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleActivityPeriod query()
 *
 * @mixin \Eloquent
 */
#[UseFactory(TitleActivityPeriodFactory::class)]
class TitleActivityPeriod extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'titles_activations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title_id',
        'started_at',
        'ended_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Get the title associated with this activation.
     *
     * @return BelongsTo<Title, $this>
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
