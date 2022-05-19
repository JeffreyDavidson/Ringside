<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Repositories\TitleRepository;

abstract class BaseTitleAction
{
    protected TitleRepository $titleRepository;

    /**
     * Create a new title action instance.
     *
     * @param  \App\Repositories\TitleRepository  $titleRepository
     */
    public function __construct(TitleRepository $titleRepository)
    {
        $this->titleRepository = $titleRepository;
    }
}
