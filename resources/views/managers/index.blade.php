<x-layouts.app>
    <x-subheader title="Managers">
        <x-slot name="actions">
            <a href="{{ route('managers.create') }}" class="btn btn-label-brand btn-bold">
                Create Managers
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Employed Managers">
            <div class="kt-portlet__body">
                <livewire:managers.employed-managers>
            </div>
        </x-portlet>

        <x-portlet title="Future Employed and Unemployed Managers">
            <div class="kt-portlet__body">
                <livewire:managers.future-employed-and-unemployed-managers>
            </div>
        </x-portlet>

        <x-portlet title="Suspended Managers">
            <div class="kt-portlet__body">
                <livewire:managers.suspended-managers>
            </div>
        </x-portlet>

        <x-portlet title="Injured Managers">
            <div class="kt-portlet__body">
                <livewire:managers.injured-managers>
            </div>
        </x-portlet>

        <x-portlet title="Released Managers">
            <div class="kt-portlet__body">
                <livewire:managers.released-managers>
            </div>
        </x-portlet>

        <x-portlet title="Retired Managers">
            <div class="kt-portlet__body">
                <livewire:managers.retired-managers>
            </div>
        </x-portlet>
    </x-content>
</x-layouts.app>
