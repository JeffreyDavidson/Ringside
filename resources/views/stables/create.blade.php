<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Create Stable</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('stables.index')" label="Stables" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Create" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Create A New Stable Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('stables.store') }}">
                @csrf
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Name:"
                        name="name"
                        placeholder="Stable Name Here"
                        :value="old('name')"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.date
                        label="Start Date:"
                        name="start_date"
                        :value="old('start_date')"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.select
                        label="Wrestlers:"
                        name="wrestlers"
                        :options="$wrestlers"
                        :selected="old('wrestlers')"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.select
                        label="Tag Teams:"
                        name="tag_teams"
                        :options="$tagTeams"
                        :selected="old('tag_teams')"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.select
                        label="Managers:"
                        name="managers"
                        :options="$managers"
                        :selected="old('managers')"
                    />
                </div>
        </div>
        <div class="card-footer">
            <x-form.buttons.submit />
            <x-form.buttons.reset />
        </div>
        </form>
    </div>
</x-layouts.app>
