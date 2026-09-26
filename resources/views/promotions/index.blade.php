<x-layouts.app>
    <x-layouts.workspace-canvas class="flex min-w-0 flex-col gap-6">
        <div x-data x-on:promotion-saved.window="window.location.reload()">
            <livewire:promotions.tables.main />
        </div>
    </x-layouts.workspace-canvas>
</x-layouts.app>
