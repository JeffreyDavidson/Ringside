<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\Event;

class EventPresenter extends Presenter
{
    /**
     * The event to be presented.
     */
    protected Event $model;

    /**
     * Retrieve the formatted event date.
     */
    public function date(): ?string
    {
        return $this->model->date?->format('F j, Y');
    }
}
