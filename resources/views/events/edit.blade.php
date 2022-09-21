<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="Events">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('events.index')" label="Events" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('events.show', $event)" :label="$event->name" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item label="Edit" />
        </x-toolbar>
    </x-slot>

    <div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Edit Event Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('events.update', $event) }}">
                @method('patch')
                @csrf
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Name:"
                        name="name"
                        placeholder="Event Name Here"
                        :value="old('name', $event->name)"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.date
                        label="Date:"
                        name="date"
                        :value="old('date', $event->date?->format('Y-m-d'))"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.select
                        label="Venue:"
                        name="venue_id"
                        :options="$venues"
                        :selected="old('venue_id', $event->venue_id)"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.textarea
                        label="Preview:"
                        name="preview"
                        placeholder="Enter a preview description of the event."
                        :value="old('preview', $event->preview)"
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
