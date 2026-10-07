@use('App\Enums\Roster\RosterMemberKind')

<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text :label="__('stables.name')" wire:model="form.name" required initial-focus />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date :label="__('activations.started_at')" wire:model="form.started_at" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date :label="__('activations.ended_at')" wire:model="form.ended_at" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.roster-combobox
            :label="__('core.wrestlers')"
            wire:model="form.wrestlers"
            :kind="RosterMemberKind::Wrestlers"
            :labels="$this->selectedRosterLabels['wrestlers']"
            multiple
        />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.roster-combobox
            :label="__('core.tag-teams')"
            wire:model="form.tag_teams"
            :kind="RosterMemberKind::TagTeams"
            :labels="$this->selectedRosterLabels['tag_teams']"
            multiple
        />
    </x-form-modal.modal-input>
</x-form-modal>
