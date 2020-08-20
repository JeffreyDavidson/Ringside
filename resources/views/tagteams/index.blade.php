<x-layouts.app>
    <x-subheader title="Tag Teams">
        <x-slot name="actions">
            <a href="{{ route('tag-teams.create') }}" class="btn btn-label-brand btn-bold">
                Create Tag Teams
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Employed Tag Teams">
            <div class="kt-portlet__body">
                <livewire:tag-teams.employed-tag-teams>
            </div>
        </x-portlet>

        <x-portlet title="Future Employed and Unemployed Tag Teams">
            <div class="kt-portlet__body">
                <livewire:tag-teams.future-employed-and-unemployed-tag-teams>
            </div>
        </x-portlet>

        <x-portlet title="Suspended Tag Teams">
            <div class="kt-portlet__body">
                <livewire:tag-teams.suspended-tag-teams>
            </div>
        </x-portlet>

        <x-portlet title="Released Tag Teams">
            <div class="kt-portlet__body">
                <livewire:tag-teams.released-tag-teams>
            </div>
        </x-portlet>

        <x-portlet title="Retired Tag Teams">
            <div class="kt-portlet__body">
                <livewire:tag-teams.retired-tag-teams>
            </div>
        </x-portlet>
    </x-content>
</x-layouts.app>
