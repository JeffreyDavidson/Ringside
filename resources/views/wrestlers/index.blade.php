<x-layouts.app>
    <x-subheader title="Wrestlers">
        <x-slot name="actions">
            <a href="{{ route('wrestlers.create') }}" class="btn btn-label-brand btn-bold">
                Create Wrestlers
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Employed Wrestlers">
            <div class="kt-portlet__body">
                <livewire:wrestlers.employed-wrestlers>
            </div>
        </x-portlet>

        <x-portlet title="Future Employed and Unemployed Wrestlers">
            <div class="kt-portlet__body">
                <livewire:wrestlers.future-employed-and-unemployed-wrestlers>
            </div>
        </x-portlet>

         <x-portlet title="Suspended Wrestlers">
            <div class="kt-portlet__body">
                <livewire:wrestlers.suspended-wrestlers>
            </div>
        </x-portlet>

         <x-portlet title="Injured Wrestlers">
            <div class="kt-portlet__body">
                <livewire:wrestlers.injured-wrestlers>
            </div>
        </x-portlet>

        <x-portlet title="Released Wrestlers">
            <div class="kt-portlet__body">
                <livewire:wrestlers.released-wrestlers>
            </div>
        </x-portlet>

        <x-portlet title="Retired Wrestlers">
            <div class="kt-portlet__body">
                <livewire:wrestlers.retired-wrestlers>
            </div>
        </x-portlet>
    </x-content>
</x-layouts.app>
