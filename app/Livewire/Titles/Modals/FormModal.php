<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Titles\Forms\Form;
use App\Models\Titles\Title;
use Illuminate\Support\Str;

/**
 * @extends BaseFormModal<Form, Title>
 */
class FormModal extends BaseFormModal
{
    public Form $form;

    protected function getFormClass(): string
    {
        return Form::class;
    }

    protected function getModelClass(): string
    {
        return Title::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.titles.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        return [
            'name' => fn() => Str::of(fake()->words(3, true))->title()->value(),
            'introduction' => fn() => fake()->optional(0.8)->paragraphs(2, true),
            'active_at' => fn() => fake()->optional(0.6)->dateTimeBetween('-1 year', 'now')?->format('Y-m-d H:i:s'),
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view($this->modalFormPath ?? 'livewire.titles.modals.form-modal');
    }
}
