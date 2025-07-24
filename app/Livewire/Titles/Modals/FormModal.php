<?php

declare(strict_types=1);

namespace App\Livewire\Titles\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Titles\Forms\CreateEditForm;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Title>
 */
class FormModal extends BaseFormModal
{
    protected function getFormClass(): string
    {
        return CreateEditForm::class;
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
            'name' => fn () => Str::of(fake()->words(2, true))->title()->append(' Title')->value(),
            'type' => fn () => fake()->randomElement(['singles', 'tag-team']),
            'introduction' => fn () => fake()->optional(0.8)->paragraphs(2, true),
            'active_at' => fn () => fake()->optional(0.6)->dateTimeBetween('-1 year', 'now')?->format('Y-m-d H:i:s'),
        ];
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit Title';
        }

        return 'Create Title';
    }

    public function openModal(mixed $modelId = null): void
    {
        // Authorization check - only administrators can access title management
        if ($modelId) {
            $title = Title::findOrFail($modelId);
            Gate::authorize('update', $title);
        } else {
            Gate::authorize('create', Title::class);
        }

        parent::openModal($modelId);
    }

    public function render(): View
    {
        return view($this->modalFormPath ?? 'livewire.titles.modals.form-modal');
    }
}
