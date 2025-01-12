<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use Illuminate\Database\Eloquent\Model;
use Livewire\Form;

abstract class LivewireBaseForm extends Form
{
    public function setModel(Model $formModel): void
    {
        if (property_exists($this, 'formModel')) {
            $this->formModel = $formModel;
            $this->fill($formModel);
            $this->loadExtraData();
        } else {
            throw new \Exception('You are missing the formModel property');
        }
    }

    public function loadExtraData(): void {}
}
