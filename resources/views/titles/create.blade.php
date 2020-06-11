<x-layouts.app>
    <x-subheader title="Titles">
        <x-slot name="actions">
            <a href="{{ route('titles.index') }}" class="btn btn-label-brand btn-bold">
                Back To Titles
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Create Title Form">
            <x-form.form class="kt-form" method="post" :action="route('titles.store')">
                <div class="kt-portlet__body">
                    @include('titles.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
