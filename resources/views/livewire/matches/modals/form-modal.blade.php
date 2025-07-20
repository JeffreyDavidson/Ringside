<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.textarea label="Preview" wire:model="form.preview" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="Match Type" wire:model="form.matchTypeId">
            <option value="">Select Match Type</option>
            @if(isset($matchTypesList))
                @foreach($matchTypesList as $matchType)
                    <option value="{{ $matchType->id }}">{{ $matchType->name }}</option>
                @endforeach
            @endif
        </x-form.inputs.select>
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="Competitors (comma separated IDs)" wire:model="form.competitors" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="Referees (comma separated IDs)" wire:model="form.referees" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="Titles (comma separated IDs)" wire:model="form.titles" />
    </x-form-modal.modal-input>
</x-form-modal>