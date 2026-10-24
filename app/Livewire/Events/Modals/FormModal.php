<?php

declare(strict_types=1);

namespace App\Livewire\Events\Modals;

use App\Actions\Events\CreateAction;
use App\Actions\Events\UpdateAction;
use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Livewire\Base\BaseFormModal;
use App\Livewire\Events\Forms\CreateEditForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Queries\Events\VenueSearchQuery;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Override;

/**
 * @extends BaseFormModal<CreateEditForm, Event>
 */
class FormModal extends BaseFormModal
{
    #[Override]
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
    #[Override]
    protected function businessErrorField(BaseBusinessException $exception): string
    {
        return match ($exception->reason()) {
            BusinessRuleReason::NameTaken => 'form.name',
            default => parent::businessErrorField($exception),
        };
    }

    /**
     * Venues whose name contains the term, for the venue search box. The edited event's own venue stays findable even
     * if it has since been deleted, so the form shows where the event is. Saving still validates the chosen venue.
     *
     * @return array<int, array{id: int, name: string}>
     */
    #[Renderless]
    public function searchVenues(VenueSearchQuery $venues, string $term): array
    {
        $this->authorizeFormAccess();

        return $venues->get($term, $this->form->storedVenueId())
            ->map(fn (Venue $venue): array => ['id' => $venue->id, 'name' => $venue->name])
            ->all();
    }

    /**
     * The name of the chosen venue, so the search box shows it when the form opens (even a deleted one).
     *
     * @return array<int, array{id: int, name: string}>
     */
    #[Computed]
    public function selectedVenueLabels(): array
    {
        return Venue::query()
            ->withTrashed()
            ->whereKey($this->form->venue_id)
            ->get()
            ->map(fn (Venue $venue): array => ['id' => $venue->id, 'name' => $venue->name])
            ->all();
    }

    protected function updateForm(): void
    {
        $this->updateAction->handle($this->form->event(), $this->form->toData());
    }

    protected function createForm(): void
    {
        $this->createAction->handle($this->form->toData(), $this->form->formPromotionId());
    }

    public function render(): View
    {
        return view('livewire.events.modals.form-modal');
    }
}
