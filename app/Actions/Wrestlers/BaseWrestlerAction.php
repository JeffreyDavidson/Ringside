<?php

namespace App\Actions\Wrestlers;

use App\Repositories\WrestlerRepository;

abstract class BaseWrestlerAction
{
    /**
     * The repository to be used for wrestlers.
     *
     * @var \App\Repositories\WrestlerRepository
     */
    protected WrestlerRepository $wrestlerRepository;

    /**
     * Create a new base wrestler action instance.
     *
     * @param  \App\Repositories\WrestlerRepository  $wrestlerRepository
     */
    public function __construct(WrestlerRepository $wrestlerRepository)
    {
        $this->wrestlerRepository = $wrestlerRepository;
    }
}
