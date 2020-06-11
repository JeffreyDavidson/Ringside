<x-layouts.app>
    <x-subheader title="Wrestlers">
        <x-slot name="actions">
            <a href="{{ route('wrestlers.index') }}" class="btn btn-label-brand btn-bold">
                Back To Wrestlers
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Edit Wrestler Form">
            <x-form.form
                method="patch"
                :action="route('wrestlers.update', $wrestler)"
            >
                <div class="kt-portlet__body">
                    @include('wrestlers.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
