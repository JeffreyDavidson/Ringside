<x-layouts.app>
    <x-sub-header title="Stables">
        <x-slot name="actions">
            <a href="{{ route('stables.index') }}" class="btn btn-label-brand btn-bold">
                Back To Stables
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Edit Stable Form">
            <x-form.form method="patch" :action="route('stables.update', $stable)">
                <div class="kt-portlet__body">
                    @include('stables.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
