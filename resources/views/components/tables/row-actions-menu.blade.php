@props([
    'label',
    'menuLabel',
])

<div class="flex justify-center" x-data="{ open: false }" x-id="['row-actions-menu']">
    <div class="relative">
        <button
            type="button"
            x-ref="button"
            @click="open = ! open"
            :aria-expanded="open"
            :aria-controls="$id('row-actions-menu')"
            aria-haspopup="true"
            aria-label="{{ $label }}"
            class="text-ringside-muted hover:bg-ringside-surface-hover hover:text-ringside-ink focus-visible:outline-ringside-ink inline-flex size-9 items-center justify-center focus-visible:outline-2 focus-visible:outline-offset-2"
        >
            <x-heroicon-m-ellipsis-vertical class="size-5" aria-hidden="true" />
        </button>
        <div
            x-cloak
            x-show="open"
            @click.outside="open = false"
            @keydown.escape.stop="
                open = false;
                $refs.button.focus();
            "
            x-anchor.fixed.bottom-start="$refs.button"
            x-transition.origin.top.left
            :id="$id('row-actions-menu')"
            class="border-ringside-line bg-ringside-surface-header text-ringside-ink z-[105] m-0 w-48 border p-1 shadow-xl"
            role="group"
            aria-label="{{ $menuLabel }}"
            data-row-actions-panel
        >
            <ul class="m-0 list-none p-0">
                {{ $slot }}
            </ul>
        </div>
    </div>
</div>
