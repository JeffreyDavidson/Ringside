<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use App\Services\Titles\TitleDeletionService;
use Illuminate\Support\Carbon;

class DeleteAction
{
    public function __construct(
        private readonly TitleDeletionService $deletion,
    ) {}

    /**
     * Delete a title.
     *
     * This handles the complete deletion workflow with business impact:
     *
     * CHAMPIONSHIP IMPACT:
     * - Ends any active championship reigns
     * - Preserves championship history for reporting
     * - No impact on past championship records or statistics
     *
     * STATUS IMPACT:
     * - Ends active/debut status if currently active
     * - Ends retirement if currently retired
     * - Preserves status history for administrative records
     *
     * OTHER CLEANUP:
     * - Soft deletes the title record
     * - Allows for future restoration if needed
     * - Maintains referential integrity with historical data
     *
     * @param  Title  $title  The title to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     */
    public function handle(Title $title, ?Carbon $deletionDate = null): void
    {
        $this->deletion->delete($title, $deletionDate ?? now());
    }
}
