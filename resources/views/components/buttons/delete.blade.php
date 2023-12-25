<x-actions-menu.form action="{{ $route }}">
    <x-actions-menu.button text="Delete" {{ $attributes->whereStartsWith('wire:click') }} />
</x-actions-menu.form>
