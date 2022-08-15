<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="Tag Teams">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('tag-teams.index')" label="Tag Teams" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item label="Create" />
        </x-toolbar>
    </x-slot>

    <div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Create Tag Team Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('tag-teams.store') }}">
                @csrf
                <div class="mb-10">
                    <x-form.inputs.text label="Name:" name="name" placeholder="Tag Team Name Here" />
                </div>
                <div class="mb-10">
                    <x-form.inputs.text label="Signature Move:" name="signature_move" placeholder="This Amazing Finisher" />
                </div>
                <div class="mb-10">
                    <x-form.inputs.date label="Start Date:" name="start_date" />
                </div>
                <div class="mb-10">
                    <x-form.inputs.select label="Tag Team Partner 1:" name="wrestlerA" :options="$wrestlers" />
                </div>
                <div class="mb-10">
                    <x-form.inputs.select label="Tag Team Partner 2:" name="wrestlerB" :options="$wrestlers" />
                </div>
        </div>
        <div class="card-footer">
            <x-form.buttons.submit />
            <x-form.buttons.reset />
        </div>
        </form>
    </div>
</x-layouts.app>
