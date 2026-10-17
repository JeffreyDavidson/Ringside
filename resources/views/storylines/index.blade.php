<x-layouts.app :title="__('storylines.title')">
    <x-layouts.workspace-canvas class="min-w-0">
        <div class="mx-auto max-w-6xl space-y-8">
            <header>
                <h1 class="font-display text-3xl">{{ __('storylines.title') }}</h1>
                <p class="text-muted-foreground mt-2">{{ __('storylines.private') }}</p>
            </header>
            <livewire:storylines.components.create />
            <livewire:storylines.tables.main />
        </div>
    </x-layouts.workspace-canvas>
</x-layouts.app>
