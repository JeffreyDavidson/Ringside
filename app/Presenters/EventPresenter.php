<?php

namespace App\Presenters;

class EventPresenter extends Presenter
{
    /**
     * Retrieve the formatted event date.
     *
     * @return string
     */
    public function date()
    {
        return $this->model->date->format('F j, Y');
    }
}
