<?php

declare(strict_types=1);

namespace App\Livewire\Events\Modals;

use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsVenuesList;
use App\Livewire\Events\Forms\CreateEditForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Event>
 */
class FormModal extends BaseFormModal
{
    use PresentsVenuesList;

    protected function getFormClass(): string
    {
        return CreateEditForm::class;
    }

    protected function getModelClass(): string
    {
        return Event::class;
    }

    protected function getModalPath(): string
    {
        return 'livewire.events.modals.form-modal';
    }

    protected function getDummyDataFields(): array
    {
        /** @var Venue|null $venue */
        $venue = Venue::query()->inRandomOrder()->first();

        return [
            'name' => fn () => Str::of(fake()->sentence(2))->title()->value(),
            'date' => fn () => fake()->dateTimeBetween('now', '+3 month')->format('Y-m-d H:i:s'),
            'venue_id' => fn () => optional($venue)->id ?? Venue::factory()->create()->id, // @phpstan-ignore-line
            'preview' => fn () => Str::of(fake()->text())->value(),
        ];
    }

    public function getModalTitle(): string
    {
        if (isset($this->model)) {
            return 'Edit Event';
        }

        return 'Create Event';
    }

    public function openModal(mixed $modelId = null): void
    {
        // Check authorization before opening modal
        if ($modelId !== null) {
            // Editing existing event - check update permission
            Gate::authorize('update', Event::class);
        } else {
            // Creating new event - check create permission
            Gate::authorize('create', Event::class);
        }

        parent::openModal($modelId);
    }

    public function render(): View
    {
        return view($this->modalFormPath ?? 'livewire.events.modals.form-modal');
    }
}
