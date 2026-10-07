<x-modal size="lg">
    <div class="space-y-5">
        <x-stables.modal-error />

        @if ($this->wrestlers->isEmpty() && $this->tagTeams->isEmpty())
            <p class="text-ringside-muted text-sm" data-test="reunite-empty">
                {{ __('stables.modals.reunite.no_members') }}
            </p>
        @else
            <p class="text-ringside-muted text-sm">
                {{ __('stables.modals.reunite.description', ['name' => $this->stable->name, 'minimum' => $this->minimumMemberCount()]) }}
            </p>

            <x-stables.member-checkboxes
                :heading="__('stables.modals.reunite.wrestlers')"
                wire:model="form.wrestlerIds"
                :members="$this->wrestlers->map(fn ($wrestler): array => ['id' => $wrestler->id, 'name' => $wrestler->name])"
            />

            <x-stables.member-checkboxes
                :heading="__('stables.modals.reunite.tag_teams')"
                wire:model="form.tagTeamIds"
                :members="$this->tagTeams->map(fn ($tagTeam): array => ['id' => $tagTeam->id, 'name' => $tagTeam->name])"
            />
        @endif
    </div>

    <x-slot:footer>
        <div class="flex flex-1 justify-end gap-2">
            <x-buttons.light wire:click="$dispatch('closeModal')">{{ __('core.form.cancel') }}</x-buttons.light>
            @if ($this->wrestlers->isNotEmpty() || $this->tagTeams->isNotEmpty())
                <x-buttons.primary
                    data-test="save-reunite"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    {{ __('stables.modals.reunite.submit') }}
                </x-buttons.primary>
            @endif
        </div>
    </x-slot:footer>
</x-modal>
