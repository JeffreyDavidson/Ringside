<?php

declare(strict_types=1);

namespace Tests\Integration\Livewire\Base;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Venues\Forms\CreateEditForm;
use App\Models\Events\Venue;

/**
 * A form modal that relies on the inherited createForm() and updateForm() stubs.
 *
 * @extends BaseFormModal<CreateEditForm, Venue>
 */
class StubFormModal extends BaseFormModal
{
    public CreateEditForm $form;

    protected function getModelClass(): string
    {
        return Venue::class;
    }

    protected function populateDummyData(): void {}

    public function render(): string
    {
        return '<div></div>';
    }
}
