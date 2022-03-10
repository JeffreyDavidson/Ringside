<x-layouts.app>
    <x-slot name="toolbar">
        <a href="{{ route('tag-teams.index') }}" class="btn btn-label-brand btn-bold">
            Back To Tag Teams
        </a>
    </x-slot>
    <x-content>
        <x-portlet title="Create Tag Team Form">
            <x-form.form
                class="kt-form"
                method="post"
                :action="route('tag-teams.store')"
                resource="Tag Teams"
                :backTo="route('tag-teams.index')"
            >
                <div class="kt-portlet__body">
                    @include('tagteams.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
