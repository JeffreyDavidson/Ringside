<?php

namespace App\Models;

use App\Models\Contracts\Employable;
use App\Models\Contracts\Injurable;
use App\Models\Contracts\Releasable;
use App\Models\Contracts\Retirable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Model;

abstract class SingleRosterMember extends Model implements Employable, Injurable, Releasable, Retirable, Suspendable
{
    use Concerns\Employable,
        Concerns\Injurable,
        Concerns\Releasable,
        Concerns\Retirable,
        Concerns\Suspendable;
}
