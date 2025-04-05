<?php

declare(strict_types=1);

namespace App\Livewire\Base;

use Illuminate\Database\Eloquent\Model;
use Livewire\Form;

/**
 * @template TForm of LivewireBaseForm
 * @template TFormModel of ?Model
 */
abstract class LivewireBaseForm extends Form
{
    /**
     * @var TFormModel
     */
    protected $formModel;

    protected string $fieldName = 'Unknown';

    /**
     * @param  TFormModel  $formModel
     */
    public function setModel($formModel): void
    {
        $this->formModel = $formModel;
        $this->fill($formModel);
        $this->loadExtraData();
    }

    public function generateModelEditName(string $fieldName): string
    {
        if (property_exists($this->formModel, $fieldName)) {
            return strval($this->formModel->{$fieldName} ?? 'Unknown');
        }

        return 'Unknown';
    }

    public function loadExtraData(): void {}
}
