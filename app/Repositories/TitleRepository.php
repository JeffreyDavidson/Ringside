<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Titles\LongestReigningChampionSummary;
use App\Data\Titles\TitleData;
use App\Enums\Titles\TitleStatus;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleActivityPeriod;
use App\Models\Titles\TitleChampionship;
use App\Models\Titles\TitleRetirement;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\Concerns\ManagesActivity;
use App\Repositories\Concerns\ManagesRetirement;
use App\Repositories\Contracts\ManagesActivity as ManagesActivityContract;
use App\Repositories\Contracts\ManagesRetirement as ManagesRetirementContract;
use App\Repositories\Contracts\TitleRepositoryInterface;
use App\Repositories\Support\BaseRepository;
use Illuminate\Support\Carbon;
use Tests\Unit\Repositories\TitleRepositoryTest;

/**
 * Repository for Title model business operations and data persistence.
 *
 * Handles all title related database operations including CRUD operations,
 * activity/retirement management, championship queries, and title-specific
 * business logic like debut, pull, and reinstatement operations.
 *
 * @see TitleRepositoryTest
 */
class TitleRepository extends BaseRepository implements ManagesActivityContract, ManagesRetirementContract, TitleRepositoryInterface
{
    /** @use ManagesActivity<TitleActivityPeriod, Title> */
    use ManagesActivity;

    /** @use ManagesRetirement<TitleRetirement, Title> */
    use ManagesRetirement;

    /**
     * Create a new title.
     */
    public function create(TitleData $titleData): Title
    {
        return Title::query()->create([
            'name' => $titleData->name,
            'type' => $titleData->type,
        ]);
    }

    /**
     * Update a title.
     */
    public function update(Title $title, TitleData $titleData): Title
    {
        $title->update([
            'name' => $titleData->name,
            'type' => $titleData->type,
        ]);

        return $title;
    }

    /**
     * Retrieve the longest reigning champion for the given title.
     *
     * Finds the championship with the longest reign duration for the specified title.
     * Calculates reign lengths using raw SQL for maximum compatibility.
     *
     * @param  Title  $title  The title to find the longest reigning champion for
     * @return LongestReigningChampionSummary|null The longest reigning champion summary, or null if no championships exist
     */
    public function getLongestReigningChampion(Title $title): ?LongestReigningChampionSummary
    {
        /** @var TitleChampionship|null $championship */
        $championship = TitleChampionship::query()
            ->where('title_id', $title->id)
            ->selectRaw('*, CASE WHEN lost_at IS NULL THEN julianday(datetime("now")) - julianday(won_at) ELSE julianday(lost_at) - julianday(won_at) END as reign_length')
            ->orderByRaw('CASE WHEN lost_at IS NULL THEN julianday(datetime("now")) - julianday(won_at) ELSE julianday(lost_at) - julianday(won_at) END DESC')
            ->with('champion')
            ->first();

        if (! $championship) {
            return null;
        }

        // Get the champion and handle potential null case
        /** @var Wrestler|TagTeam|null $champion */
        $champion = $championship->champion;
        $championName = $champion?->name ?? 'Unknown';

        // Access the calculated reign_length attribute
        /** @var int $reignLength */
        $reignLength = (int) ($championship->getAttribute('reign_length') ?? 0);

        return new LongestReigningChampionSummary(
            championName: $championName,
            reignLengthInDays: $reignLength,
            wonAt: $championship->won_at,
            lostAt: $championship->lost_at,
        );
    }

    /**
     * Debut a title by creating an activity period.
     */
    public function createDebut(Title $title, Carbon $debutDate, ?string $notes = null): void
    {
        $this->createActivity($title, $debutDate);
        
        // Update the title status based on debut date
        $status = $debutDate->isFuture() ? TitleStatus::PendingDebut : TitleStatus::Active;
        $title->update(['status' => $status]);
        
        // TODO: Add notes handling if TitleActivityPeriod model supports notes column
    }

    /**
     * Pull a title from active competition by ending its activity period.
     */
    public function pull(Title $title, Carbon $pullDate, ?string $notes = null): void
    {
        $this->endActivity($title, $pullDate);
        
        // Update the title status to Inactive when pulled
        $title->update(['status' => TitleStatus::Inactive]);
        
        // TODO: Add notes handling if TitleActivityPeriod model supports notes column
    }

    /**
     * Reinstate a title to active competition by creating a new activity period.
     */
    public function createReinstatement(Title $title, Carbon $reinstateDate, ?string $notes = null): void
    {
        $this->createActivity($title, $reinstateDate);
        
        // Update the title status to Active when reinstated
        $title->update(['status' => TitleStatus::Active]);
        
        // TODO: Add notes handling if TitleActivityPeriod model supports notes column
    }

    /**
     * Restore a soft-deleted title.
     */
    public function restore(Title $title): void
    {
        $title->restore();
    }

    /**
     * Activate a title by creating an activity period.
     */
    public function activate(Title $title, Carbon $startDate): Title
    {
        $this->createActivity($title, $startDate);

        return $title;
    }

    /**
     * Deactivate a title by ending its activity period.
     */
    public function deactivate(Title $title, Carbon $endDate): Title
    {
        $this->endActivity($title, $endDate);

        return $title;
    }

    /**
     * Retire a title by creating a retirement period.
     */
    public function retire(Title $title, Carbon $startDate): Title
    {
        // End current activity if active (retirement ends activity)
        if ($title->currentActivityPeriod) {
            $title->currentActivityPeriod->update(['ended_at' => $startDate]);
        }

        $title->retirements()->create(['started_at' => $startDate]);
        // Note: Title status is not updated to "Retired" since TitleStatus doesn't include retired
        // Retirement is tracked separately through the retirements relationship

        return $title;
    }

    /**
     * Unretire a title by ending its retirement period.
     */
    public function unretire(Title $title, Carbon $endDate): Title
    {
        $title->currentRetirement()->update(['ended_at' => $endDate]);
        // Note: Title status remains unchanged as retirement is tracked separately

        return $title;
    }
}
