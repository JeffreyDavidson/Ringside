<x-layouts.app>
    <x-slot name="toolbar">
        <a href="{{ route('managers.index') }}" class="btn btn-label-brand btn-bold">
            Back To Managers
        </a>
    </x-slot>
    <x-content>
        <x-portlet title="Create Manager Form">
            <x-form.form
                class="kt-form"
                method="post"
                :action="route('managers.store')"
                resource="Managers"
                :backTo="route('managers.index')"
            >
                <div class="kt-portlet__body">
                    @include('managers.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
