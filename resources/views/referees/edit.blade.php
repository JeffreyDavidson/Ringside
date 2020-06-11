<x-layouts.app>
    <x-subheader title="Referees">
        <x-slot name="actions">
            <a href="{{ route('referees.index') }}" class="btn btn-label-brand btn-bold">
                Back To Referees
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Edit Manager Form">
            <x-form.form class="kt-form" method="patch" :action="route('referees.update', $referee)">
                <div class="kt-portlet__body">
                    @include('referees.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
