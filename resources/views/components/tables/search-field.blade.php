@props(['id', 'model', 'value' => '', 'label', 'placeholder', 'clearLabel'])

<div class="border-outline focus-within:outline-foreground relative flex min-h-11 w-full items-center gap-2 border px-3 focus-within:outline-2 focus-within:outline-offset-2 sm:max-w-sm">
    <x-heroicon-o-magnifying-glass class="text-muted-foreground size-4 shrink-0" aria-hidden="true" />
    <label for="{{ $id }}" class="sr-only">{{ $label }}</label>
    <input
        id="{{ $id }}"
        type="text"
        inputmode="search"
        role="searchbox"
        wire:model.live.debounce.300ms="{{ $model }}"
        placeholder="{{ $placeholder }}"
        @class([
            'text-foreground placeholder:text-muted-foreground m-0 w-full min-w-0 border-0 bg-transparent px-0 py-0 text-sm outline-none focus:ring-0',
            'pe-11' => $value !== '',
        ])
    />
    @if ($value !== '')
        <button
            type="button"
            wire:click="$set('{{ $model }}', '')"
            aria-label="{{ $clearLabel }}"
            class="text-muted-foreground hover:text-foreground focus-visible:outline-foreground absolute end-3 top-1/2 inline-flex size-11 -translate-y-1/2 cursor-pointer items-center justify-center focus-visible:outline-2"
        >
            <x-heroicon-o-x-mark class="size-4" aria-hidden="true" />
        </button>
    @endif
</div>
