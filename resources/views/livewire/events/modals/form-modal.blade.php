<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text :label="__('events.name')" wire:model="form.name" required initial-focus />
    </x-form-modal.modal-input>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-test="event-schedule-grid">
        <x-form-modal.modal-input>
            <x-form.input
                type="datetime-local"
                :label="__('events.date_time')"
                wire:model="form.date"
                class="event-date-time [color-scheme:dark]"
            />
        </x-form-modal.modal-input>

        <x-form-modal.modal-input>
            <x-form.inputs.roster-combobox
                :label="__('events.venue')"
                wire:model="form.venue_id"
                search-method="searchVenues"
                nullable
                :empty-message="__('events.no_venue_matches')"
                :labels="$this->selectedVenueLabels"
            />
        </x-form-modal.modal-input>
    </div>

    <x-form-modal.modal-input>
        <x-form.inputs.textarea :label="__('events.preview')" wire:model="form.preview" />
    </x-form-modal.modal-input>
</x-form-modal>
