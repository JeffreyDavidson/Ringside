<x-layouts.app>
    <x-subheader title="Venues">
        <x-slot name="actions">
            <a href="{{ route('venues.index') }}" class="btn btn-label-brand btn-bold">
                Back To Venues
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Create Venue Form">
            <x-form.form class="kt-form" method="post" :action="route('venues.store')">
                <div class="kt-portlet__body">
                    @include('venues.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
