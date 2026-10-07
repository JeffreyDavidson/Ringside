<x-modal size="sm">
    <div class="space-y-5">
        <x-stables.modal-error />

        @if ($this->candidates->isEmpty())
            <p class="text-ringside-muted text-sm">{{ __('stables.modals.merge.no_candidates') }}</p>
        @else
            <x-form.inputs.select
                id="otherStableId"
                :label="__('stables.modals.merge.other_stable')"
                wire:model.live="form.otherStableId"
                :options="$this->candidates->pluck('name', 'id')->all()"
                :placeholder="__('stables.modals.merge.select_stable')"
                required
            />

            <p class="text-ringside-muted text-sm" data-test="merge-summary">
                @if ($this->selectedStable)
                    {{ __('stables.modals.merge.summary', ['name' => $this->stable->name, 'other' => $this->selectedStable->name]) }}
                @else
                    {{ __('stables.modals.merge.summary_pending', ['name' => $this->stable->name]) }}
                @endif
            </p>
        @endif
    </div>

    <x-slot:footer>
        <div class="flex flex-1 justify-end gap-2">
            <x-buttons.light wire:click="$dispatch('closeModal')">{{ __('core.form.cancel') }}</x-buttons.light>
            <x-buttons.primary data-test="save-merge" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                {{ __('stables.modals.merge.submit') }}
            </x-buttons.primary>
        </div>
    </x-slot:footer>
</x-modal>
