<?php

namespace App\Actions\Referees;

use App\Repositories\RefereeRepository;

abstract class BaseRefereeAction
{
    protected RefereeRepository $refereeRepository;

    /**
     * Create a new base referee action instance.
     *
     * @param  \App\Repositories\RefereeRepository  $refereeRepository
     */
    public function __construct(RefereeRepository $refereeRepository)
    {
        $this->refereeRepository = $refereeRepository;
    }
}
