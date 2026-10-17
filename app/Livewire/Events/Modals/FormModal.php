<?php

declare(strict_types=1);

namespace App\Livewire\Events\Modals;

use App\Actions\Events\CreateAction;
use App\Actions\Events\UpdateAction;
use App\Builders\Events\VenueBuilder;
use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Concerns\Data\PresentsVenuesList;
use App\Livewire\Events\Forms\CreateEditForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * @extends BaseFormModal<CreateEditForm, Event>
 *
 * @property-read array<int|string,string|null> $getVenues
 */
class FormModal extends BaseFormModal
{
    use PresentsVenuesList;

    #[\Override]
    protected ?string $businessErrorField = 'form.venue_id';

    public CreateEditForm $form;

    private CreateAction $createAction;

    private UpdateAction $updateAction;

    public function boot(CreateAction $createAction, UpdateAction $updateAction): void
    {
        $this->createAction = $createAction;
        $this->updateAction = $updateAction;
    }

    protected function getModelClass(): string
    {
        return Event::class;
    }

    protected function populateDummyData(): void
    {
        $this->form->name = Str::of(fake()->sentence(2))->title()->value();
        $this->form->date = fake()->dateTimeBetween('now', '+3 month')->format('Y-m-d\\TH:i');
        $venue = Venue::query()->inRandomOrder()->first();

        if ($venue !== null) {
            $this->form->venue_id = $venue->id;
        }
        $this->form->preview = Str::of(fake()->text())->value();
    }

    /** A name taken since validation shows on the name field; any other failure on the venue. */
    #[\Override]
    protected function businessErrorField(BaseBusinessException $exception): string
    {
        return match ($exception->reason()) {
            BusinessRuleReason::NameTaken => 'form.name',
            default => parent::businessErrorField($exception),
        };
    }

    /**
     * The edited event's own venue is listed even if it has since been deleted, so the select shows where the event is.
     *
     * @return VenueBuilder<Venue>
     */
    protected function venuesQuery(): VenueBuilder
    {
        $storedVenueId = isset($this->form) ? $this->form->storedVenueId() : null;

        if ($storedVenueId === null) {
            return Venue::query();
        }

        return Venue::query()->withTrashed()->where(
            fn (Builder $query): Builder => $query->whereNull('deleted_at')->orWhere('id', $storedVenueId),
        );
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->event(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData());
    }

    public function render(): View
    {
        return view('livewire.events.modals.form-modal');
    }
}
