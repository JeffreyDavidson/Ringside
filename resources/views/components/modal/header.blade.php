<div class="flex items-center justify-between border-b border-solid border-gray-200 ps-5 pe-5 pr-2.5 py-2.5">
    <h3 class="text-sm leading-5 font-semibold text-gray-900">{{ $this->getModalTitle() }}</h3>
    <x-buttons.light size="xs" iconOnly wire:click="$dispatch('closeModal')">
        <x-heroicon-m-x-mark class="size-4 text-gray-500" />
    </x-buttons.light>
</div>
