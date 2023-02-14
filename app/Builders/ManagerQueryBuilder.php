<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\ManagerStatus;

/**
 * @template TModelClass of \App\Models\Manager
 *
 * @extends SingleRosterMemberQueryBuilder<\App\Models\Manager>
 */
class ManagerQueryBuilder extends SingleRosterMemberQueryBuilder
{
    /**
     * Scope a query to only include available managers.
     */
    public function available(): ManagerQueryBuilder
    {
        return $this->where('status', ManagerStatus::AVAILABLE);
    }
}
