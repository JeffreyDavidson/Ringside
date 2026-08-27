<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Models\Titles\Title;
use App\Services\TitleRetirementService;
use Illuminate\Support\Carbon;

class RetireAction
{
    public function __construct(
        private readonly TitleRetirementService $retirement,
    ) {}

    /**
     * Retire a title and permanently end its championship lineage.
     *
     * This handles the complete title retirement workflow:
     * - Validates the title can be retired (currently active or inactive)
     * - Ends active status if currently active
     * - Creates retirement record to permanently retire the championship
     * - Makes the title unavailable for future competition permanently
     * - Preserves championship history and lineage for legacy purposes
     * - Ends any current championship reigns associated with the title
     *
     * @param  Title  $title  The title to retire
     * @param  Carbon|null  $retirementDate  The retirement date (defaults to now)
     */
    public function handle(Title $title, ?Carbon $retirementDate = null): void
    {
        $this->retirement->retire($title, $retirementDate ?? now());
    }
}
