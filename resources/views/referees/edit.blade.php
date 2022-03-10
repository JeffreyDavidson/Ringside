<x-layouts.app>
    <x-slot name="toolbar">
        <a href="{{ route('referees.index') }}" class="btn btn-label-brand btn-bold">
            Back To Referees
        </a>
    </x-slot>
    <x-content>
        <x-portlet title="Edit Manager Form">
            <x-form.form
                class="kt-form"
                method="patch"
                :action="route('referees.update', $referee)"
                resource="Referees"
                :backTo="route('referees.index')"
            >
                <div class="kt-portlet__body">
                    @include('referees.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
