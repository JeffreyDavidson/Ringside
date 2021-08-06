<?php

namespace App\Services;

use App\Models\Title;
use App\Repositories\TitleRepository;
use App\Strategies\Activation\TitleActivationStrategy;
use App\Strategies\Deactivation\TitleDeactivationStrategy;

class TitleService
{
    /**
     * The repository implementation.
     *
     * @var \App\Repositories\TitleRepository
     */
    protected $titleRepository;

    /**
     * Create a new title service instance.
     *
     * @param \App\Repositories\TitleRepository $titleRepository
     */
    public function __construct(TitleRepository $titleRepository)
    {
        $this->titleRepository = $titleRepository;
    }

    /**
     * Create a title.
     *
     * @param  array $data
     * @return \App\Models\Title
     */
    public function create(array $data)
    {
        $title = $this->titleRepository->create($data);

        if ($data['activated_at']) {
            (new TitleActivationStrategy($title))->activate($data['activated_at']);
        }

        return $title;
    }

    /**
     * Activate a title.
     *
     * @param  \App\Models\Title $title
     * @return void
     */
    public function activate(Title $title)
    {
        (new TitleActivationStrategy($title))->activate();
    }

    /**
     * Deactivate a title.
     *
     * @param  \App\Models\Title $title
     * @return void
     */
    public function deactivate(Title $title)
    {
        (new TitleDeactivationStrategy($title))->deactivate();
    }
}
