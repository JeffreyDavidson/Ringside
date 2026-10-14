<x-layouts.app :title="$storyline->title">
    <x-layouts.workspace-canvas class="min-w-0">
        <livewire:storylines.components.manage :storylineId="$storyline->id" />
    </x-layouts.workspace-canvas>
</x-layouts.app>
