<x-layouts.app>
    <x-subheader title="Events">
        <x-slot name="actions">
            <a href="{{ route('events.index') }}" class="btn btn-label-brand btn-bold">
                Back To Events
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Create Event Form">
            <x-form.form class="kt-form" method="post" :action="route('events.store')">
                <div class="kt-portlet__body">
                    @include('events.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
