<x-layouts.app>
    <x-subheader title="Referees">
        <x-slot name="actions">
            <a href="{{ route('referees.create') }}" class="btn btn-label-brand btn-bold">
                Create Referees
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Employed Referees">
            <div class="kt-portlet__body">
                <livewire:referees.employed-referees>
            </div>
        </x-portlet>

        <x-portlet title="Released Referees">
            <div class="kt-portlet__body">
                <livewire:referees.released-referees>
            </div>
        </x-portlet>

        <x-portlet title="Retired Referees">
            <div class="kt-portlet__body">
                <livewire:referees.retired-referees>
            </div>
        </x-portlet>
    </x-content>
</x-layouts.app>
