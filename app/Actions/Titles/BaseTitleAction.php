<?php

declare(strict_types=1);

namespace App\Actions\Titles;

use App\Repositories\TitleRepository;

abstract class BaseTitleAction
{
    /**
     * Create a new base title action instance.
     */
    public function __construct(protected TitleRepository $titleRepository) {}
}
