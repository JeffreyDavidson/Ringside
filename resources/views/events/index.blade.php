<x-layouts.app>
    <x-subheader title="Events">
        <x-slot name="actions">
            <a href="{{ route('events.create') }}" class="btn btn-label-brand btn-bold">
                Create Events
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Scheduled Events">
            <div class="kt-portlet__body">
                <livewire:events.scheduled-events>
            </div>
        </x-portlet>

        <x-portlet title="Past Events">
            <div class="kt-portlet__body">
                <livewire:events.past-events>
            </div>
        </x-portlet>
    </x-content>
</x-layouts.app>
