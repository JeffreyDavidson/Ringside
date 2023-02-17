<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Repositories\ManagerRepository;

abstract class BaseManagerAction
{
    protected ManagerRepository $managerRepository;

    /**
     * Create a new base manager action instance.
     */
    public function __construct(ManagerRepository $managerRepository)
    {
        $this->managerRepository = $managerRepository;
    }
}
