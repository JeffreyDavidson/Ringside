<?php

namespace App\Builders;

use App\Enums\ManagerStatus;

class ManagerQueryBuilder extends SingleRosterMemberQueryBuilder
{
    /**
     * Scope a query to only include available managers.
     *
     * @return $this
     */
    public function available()
    {
        return $this->where('status', ManagerStatus::available());
    }
}
