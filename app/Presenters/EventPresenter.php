<?php

declare(strict_types=1);

namespace App\Presenters;

class EventPresenter extends Presenter
{
    /**
     * The event to be presented.
     *
     * @var \App\Models\Event
     */
    protected $model;

    /**
     * Retrieve the formatted event date.
     */
    public function date(): ?string
    {
        return $this->model->date?->format('F j, Y');
    }
}
