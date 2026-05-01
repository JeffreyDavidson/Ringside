<?php

declare(strict_types=1);

namespace App\Models\Titles;

use App\Enums\Shared\ActivationStatus;
use Database\Factories\Titles\TitleStatusChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $title_id
 * @property ActivationStatus $status
 * @property Carbon $changed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Title|null $title
 *
 * @method static \Database\Factories\Titles\TitleStatusChangeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleStatusChange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleStatusChange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TitleStatusChange query()
 *
 * @mixin \Eloquent
 */
#[Table('titles_status_changes')]
#[Fillable('title_id', 'status', 'changed_at')]
#[UseFactory(TitleStatusChangeFactory::class)]
class TitleStatusChange extends Model
{
    /** @use HasFactory<TitleStatusChangeFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ActivationStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    /**
     * Get the title associated with this status change.
     *
     * @return BelongsTo<Title, $this>
     */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
