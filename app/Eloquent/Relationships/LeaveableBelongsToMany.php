<?php

namespace App\Eloquent\Relationships;

use App\Eloquent\Concerns\IsLeaveableBelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LeaveableBelongsToMany extends BelongsToMany
{
    use IsLeaveableBelongsToMany;

    protected $pivotColumns = ['left_at', 'joined_at'];
}
