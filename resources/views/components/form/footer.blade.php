<div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex">
        @env('local')
            @empty($this->modelForm->formModel)
                <x-button
                    variant="secondary"
                    class="!border-ringside-line !text-ringside-muted hover:!bg-ringside-surface hover:!text-ringside-ink !h-9 !rounded-none !border !bg-transparent"
                    wire:click="fillDummyFields"
                >
                    Auto fill
                </x-button>
            @endempty
        @endenv
    </div>
    <div class="flex justify-end gap-2">
        <x-button
            variant="secondary"
            class="!border-ringside-line !text-ringside-muted hover:!bg-ringside-surface hover:!text-ringside-ink !h-9 !rounded-none !border !bg-transparent"
            wire:click="clear"
        >
            Clear
        </x-button>
        <x-button variant="ringside" size="md" wire:click="save"> Save </x-button>
    </div>
</div>
