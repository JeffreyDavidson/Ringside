<x-layouts.app>
    <x-subheader title="Stables">
        <x-slot name="actions">
            <a href="{{ route('stables.index') }}" class="btn btn-label-brand btn-bold">
                Back To Stables
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Create Stable Form">
            <x-form.form class="kt-form" method="post" :action="route('stables.store')">
                <div class="kt-portlet__body">
                    @include('stables.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
