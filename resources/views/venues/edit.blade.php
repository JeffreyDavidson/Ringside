<x-layouts.app>
    <x-subheader title="Venues">
        <x-slot name="actions">
            <a href="{{ route('venues.index') }}" class="btn btn-label-brand btn-bold">
                Back To Venues
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Edit Venue Form">
            <x-form.form method="patch" :action="route('venues.update', $venue)">
                <div class="kt-portlet__body">
                    @include('venues.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
