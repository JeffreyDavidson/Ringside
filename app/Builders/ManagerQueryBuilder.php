<?php

namespace App\Builders;

use App\Enums\ManagerStatus;

class ManagerQueryBuilder extends SingleRosterMemberQueryBuilder
{
    /**
     * Scope a query to only include available managers.
     *
     * @return \App\Builders\ManagerQueryBuilder
     */
    public function available()
    {
        return $this->where('status', ManagerStatus::available());
    }
}
