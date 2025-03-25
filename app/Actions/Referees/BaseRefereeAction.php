<?php

declare(strict_types=1);

namespace App\Actions\Referees;

use App\Repositories\RefereeRepository;

abstract class BaseRefereeAction
{
    /**
     * Create a new base referee action instance.
     */
    public function __construct(protected RefereeRepository $refereeRepository) {}
}
