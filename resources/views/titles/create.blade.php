<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="Titles">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('titles.index')" label="Titles" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item label="Create" />
        </x-toolbar>
    </x-slot>

    <div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Create A New Title Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('titles.store') }}">
                @csrf
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Name:"
                        name="name"
                        placeholder="Title Name Here"
                        :value="old('name')"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.date
                        label="Activation Date:"
                        name="activation_date"
                        :value="old('activation_date')"
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
