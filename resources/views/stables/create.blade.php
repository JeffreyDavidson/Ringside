<x-layouts.app>
    <x-slot name="toolbar">
        <a href="{{ route('stables.index') }}" class="btn btn-label-brand btn-bold">
            Back To Stables
        </a>
    </x-slot>
    <x-content>
        <x-portlet title="Create Stable Form">
            <x-form.form
                class="kt-form"
                method="post"
                :action="route('stables.store')"
                resource="Stables"
                :backTo="route('stables.index')"
            >
                <div class="kt-portlet__body">
                    @include('stables.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
