<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="Stables">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('stables.index')" label="Stables" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('stables.show', $stable)" :label="$stable->name" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item label="Edit" />
        </x-toolbar>
    </x-slot>

    <div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Edit Stable Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('stables.update', $stable) }}">
                @method('patch')
                @csrf
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Name:"
                        name="name"
                        placeholder="Stable Name Here"
                        :value="old('name', $stable->name)"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.date
                        label="Start Date:"
                        name="start_date"
                        :value="old('start_date', $stable->started_at?->format('Y-m-d'))"
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
