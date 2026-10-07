<x-modal size="lg">
    <div class="space-y-5">
        <x-stables.modal-error />

        <p class="text-ringside-muted text-sm">
            {{ __('stables.modals.split.description', ['name' => $this->stable->name, 'minimum' => $this->minimumMemberCount()]) }}
        </p>

        <x-form.inputs.text
            :label="__('stables.modals.split.new_name')"
            wire:model="form.name"
            required
            initial-focus
        />

        <x-stables.member-checkboxes
            :heading="__('stables.modals.split.wrestlers')"
            wire:model="form.wrestlerIds"
            :members="$this->wrestlers"
        />

        <x-stables.member-checkboxes
            :heading="__('stables.modals.split.tag_teams')"
            wire:model="form.tagTeamIds"
            :members="$this->tagTeams"
        />
    </div>

    <x-slot:footer>
        <div class="flex flex-1 justify-end gap-2">
            <x-buttons.light wire:click="$dispatch('closeModal')">{{ __('core.form.cancel') }}</x-buttons.light>
            <x-buttons.primary data-test="save-split" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                {{ __('stables.modals.split.submit') }}
            </x-buttons.primary>
        </div>
    </x-slot:footer>
</x-modal>
