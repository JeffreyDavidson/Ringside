<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Models\Roster\Managers\Manager;
use App\Services\Roster\Relationships\ManagerAssignmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EndCurrentRelationshipsAction
{
    public function __construct(private readonly ManagerAssignmentService $managerAssignments) {}

    public function handle(Manager $manager, Carbon $effectiveDate): void
    {
        DB::transaction(function () use ($manager, $effectiveDate): void {
            $lockedManager = Manager::query()
                ->whereKey($manager->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->managerAssignments->endCurrentAssignments($lockedManager, $effectiveDate);
        });
    }
}
