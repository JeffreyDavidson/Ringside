<x-layouts.app>
    <x-subheader title="Managers">
        <x-slot name="actions">
            <a href="{{ route('managers.index') }}" class="btn btn-label-brand btn-bold">
                Back To Managers
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Create Manager Form">
            <x-form.form class="kt-form" method="post" :action="route('managers.store')">
                <div class="kt-portlet__body">
                    @include('managers.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
