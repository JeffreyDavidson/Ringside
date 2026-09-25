@props(['id', 'model', 'value' => '', 'label', 'placeholder', 'clearLabel'])

<div class="border-ringside-line focus-within:border-ringside-ink relative flex min-h-11 w-full items-center gap-2 border px-3 sm:max-w-sm">
    <x-heroicon-o-magnifying-glass class="text-ringside-muted size-4 shrink-0" aria-hidden="true" />
    <label for="{{ $id }}" class="sr-only">{{ $label }}</label>
    <input
        id="{{ $id }}"
        type="text"
        inputmode="search"
        role="searchbox"
        wire:model.live.debounce.300ms="{{ $model }}"
        placeholder="{{ $placeholder }}"
        @class([
            'text-ringside-ink placeholder:text-ringside-muted m-0 w-full min-w-0 border-0 bg-transparent px-0 py-0 text-sm outline-none focus:ring-0',
            'pe-11' => $value !== '',
        ])
    />
    @if ($value !== '')
        <button
            type="button"
            wire:click="$set('{{ $model }}', '')"
            aria-label="{{ $clearLabel }}"
            class="text-ringside-muted hover:text-ringside-ink focus-visible:outline-ringside-ink absolute end-3 top-1/2 inline-flex size-11 -translate-y-1/2 cursor-pointer items-center justify-center focus-visible:outline-2"
        >
            <x-heroicon-o-x-mark class="size-4" aria-hidden="true" />
        </button>
    @endif
</div>
