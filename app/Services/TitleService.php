<?php

namespace App\Services;

use App\Models\Title;
use App\Repositories\TitleRepository;
use App\Strategies\Activation\TitleActivationStrategy;
use App\Strategies\Deactivation\TitleDeactivationStrategy;
use App\Strategies\Retirement\TitleRetirementStrategy;
use App\Strategies\Unretire\TitleUnretireStrategy;

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
     * Update a title.
     *
     * @param  \App\Models\Title $title
     * @param  array $data
     * @return \App\Models\Title $title
     */
    public function update(Title $title, array $data)
    {
        $this->titleRepository->update($title, $data);

        if ($data['activated_at']) {
            $this->activateOrUpdateActivation($title, $data['activated_at']);
        }

        return $title;
    }

    /**
     * Update the activation date for a title.
     *
     * @param  \App\Models\Title $title
     * @param  string $startDate
     * @return \App\Models\Stable
     */
    public function activateOrUpdateActivation(Title $title, string $activationDate)
    {
        if ($title->isNotInActivation()) {
            return (new TitleActivationStrategy($title))->activate($activationDate);
        }

        if ($title->hasFutureActivation() && $title->futureActivation->started_at->ne($activationDate)) {
            return $title->futureActivation()->update(['started_at' => $activationDate]);
        }
    }

    /**
     * Delete a title.
     *
     * @param  \App\Models\Title $title
     * @return void
     */
    public function delete(Title $title)
    {
        $this->titleRepository->delete($title);
    }

    /**
     * Restore a title.
     *
     * @param  \App\Models\Title $title
     * @return void
     */
    public function restore(Title $title)
    {
        $this->titleRepository->restore($title);
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

    /**
     * Retire a title.
     *
     * @param  \App\Models\Title $title
     * @return void
     */
    public function retire(Title $title)
    {
        (new TitleRetirementStrategy($title))->retire();
    }

    /**
     * Unretire a title.
     *
     * @param  \App\Models\Title $title
     * @return void
     */
    public function unretire(Title $title)
    {
        (new TitleUnretireStrategy($title))->unretire();
    }
}
