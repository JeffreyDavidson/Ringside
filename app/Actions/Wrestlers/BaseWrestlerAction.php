<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Repositories\WrestlerRepository;

abstract class BaseWrestlerAction
{
    /**
     * Create a new base wrestler action instance.
     */
    public function __construct(protected WrestlerRepository $wrestlerRepository)
    {
    }
}
