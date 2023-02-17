<?php

declare(strict_types=1);

namespace App\Actions\Managers;

use App\Repositories\ManagerRepository;

abstract class BaseManagerAction
{
    public function __construct(protected ManagerRepository $managerRepository)
    {
    }
}
