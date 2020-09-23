<x-layouts.app>
    <x-sub-header title="Tag Teams">
        <x-slot name="actions">
            <a href="{{ route('tag-teams.index') }}" class="btn btn-label-brand btn-bold">
                Back To Tag Teams
            </a>
        </x-slot>
    </x-subheader>
    <x-content>
        <x-portlet title="Edit Tag Team Form">
            <x-form.form method="patch" :action="route('tag-teams.update', $tagTeam)">
                <div class="kt-portlet__body">
                    @include('tagteams.partials.form')
                </div>
            </x-form>
        </x-portlet>
    </x-content>
</x-layouts.app>
