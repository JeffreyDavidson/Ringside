<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Edit Tag Team</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('tag-teams.index')" label="Tag Teams" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('tag-teams.show', $tagTeam)" :label="$tagTeam->name" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Edit" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Edit Tag Team Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('tag-teams.update', $tagTeam) }}">
                @method('patch')
                @csrf
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Name:"
                        name="name"
                        placeholder="Tag Team Name Here"
                        :value="old('name', $tagTeam->name)"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Signature Move:"
                        name="signature_move"
                        placeholder="This Amazing Finisher"
                        :value="old('signature_move', $tagTeam->signature_move)"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.date
                        label="Start Date:"
                        name="start_date"
                        :value="old('start_date', $tagTeam->started_at?->format('Y-m-d'))"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.select
                        label="Tag Team Partner 1:"
                        name="wrestlerA"
                        :options="$wrestlers"
                        :selected="old('wrestlerA', $tagTeam->currentWrestlers?->first()?->id)"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.select
                        label="Tag Team Partner 2:"
                        name="wrestlerB"
                        :options="$wrestlers"
                        :selected="old('wrestlerB', $tagTeam->currentWrestlers?->last()?->id)"
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
