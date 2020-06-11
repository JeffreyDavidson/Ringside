<x-layouts.app>
    <x-subheader title="Stables">
        <x-slot name="actions">
            <a href="{{ route('stables.create') }}" class="btn btn-label-brand btn-bold">
                Create Stables
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Active Stables">
            <div class="kt-portlet__body">
                <livewire:stables.active-stables>
            </div>
        </x-portlet>

        <x-portlet title="Inactive Stables">
            <div class="kt-portlet__body">
                <livewire:stables.inactive-stables>
            </div>
        </x-portlet>

        <x-portlet title="Retired Stables">
            <div class="kt-portlet__body">
                <livewire:stables.retired-stables>
            </div>
        </x-portlet>
    </x-content>
</x-layouts.app>
