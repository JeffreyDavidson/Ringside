<div class="border-border flex shrink-0 items-center justify-between border-b px-6 py-5">
    <h2 id="modal-title" class="text-foreground text-lg leading-6 font-semibold">{{ $this->getModalTitle() }}</h2>
    <x-buttons.light
        size="sm"
        iconOnly
        class="!border-border !text-muted-foreground hover:!bg-surface hover:!text-foreground !rounded-none !border !bg-transparent"
        :aria-label="__('core.modal.close')"
        wire:click="$dispatch('closeModal')"
    >
        <x-heroicon-m-x-mark class="size-4" />
    </x-buttons.light>
</div>
