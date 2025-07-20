<x-form-modal>
    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('tag-teams.name') }}" wire:model="form.name" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.text label="{{ __('tag-teams.signature_move') }}" wire:model="form.signature_move" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.date label="{{ __('employments.started_at') }}" wire:model="form.employment_date" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('tag-teams.wrestlerA') }}" wire:model="form.wrestlerA" :options="$this->getWrestlers"
            selected="form.wrestlerA" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <x-form.inputs.select label="{{ __('tag-teams.wrestlerB') }}" wire:model="form.wrestlerB" :options="$this->getWrestlers"
            selected="form.wrestlerB" />
    </x-form-modal.modal-input>

    <x-form-modal.modal-input>
        <div class="flex flex-col space-y-2">
            <label class="font-medium text-2sm leading-none text-gray-900">{{ __('tag-teams.managers') }}</label>
            <select
                class="font-medium text-2sm leading-none bg-light-active rounded-md h-10 ps-3 pe-3 border border-solid border-gray-300 text-gray-700 focus:border-primary"
                wire:model="form.managers" multiple>
                @if(isset($this->getManagers))
                    @foreach ($this->getManagers as $key => $value)
                        <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                @endif
            </select>
            @error('form.managers')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
    </x-form-modal.modal-input>
</x-form-modal>
