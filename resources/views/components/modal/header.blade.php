<div class="border-ringside-line flex shrink-0 items-center justify-between border-b px-6 py-5">
    <h2 id="modal-title" class="text-ringside-ink text-lg leading-6 font-semibold">{{ $this->getModalTitle() }}</h2>
    <x-buttons.light
        size="sm"
        iconOnly
        class="!border-ringside-line !text-ringside-muted hover:!bg-ringside-surface hover:!text-ringside-ink !rounded-none !border !bg-transparent"
        aria-label="Close dialog"
        wire:click="$dispatch('closeModal')"
    >
        <x-heroicon-m-x-mark class="size-4" />
    </x-buttons.light>
</div>
