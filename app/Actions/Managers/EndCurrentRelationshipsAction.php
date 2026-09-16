<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EndCurrentRelationshipsAction
{
    public function __construct(private readonly EndManagerAssignmentsForManagerAction $endManagerAssignmentsAction) {}

    public function handle(Manager $manager, Carbon $effectiveDate): void
    {
        DB::transaction(function () use ($manager, $effectiveDate): void {
            $lockedManager = $manager->refreshForUpdate();

            $this->endManagerAssignmentsAction->handle($lockedManager, $effectiveDate);
        });
    }
}
