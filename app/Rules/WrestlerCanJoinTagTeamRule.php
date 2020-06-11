<?php

namespace App\Rules;

use App\Rules\CannotBeEmployedAfterDate;
use App\Rules\CannotBeHindered;
use App\Rules\CannotBelongToTagTeam;
use Illuminatech\Validation\Composite\CompositeRule;

class WrestlerCanJoinTagTeamRule extends CompositeRule
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    protected function rules(): array
    {
        return [
            new CannotBeEmployedAfterDate(request('started_at')),
            new CannotBeHindered,
            new CannotBelongToTagTeam,
        ];
    }
}
