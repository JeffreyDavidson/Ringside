<x-layouts.app>
    <x-slot name="toolbar">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Create Event
        </h2>
    </x-slot>

    <x-content>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Event Form</h3>
            </div>
            <div class="card-body">
                <x-form.form
                    action="{{ route('events.store') }}"
                    backTo="{{ route('events.index') }}"
                    resource="Events"
                >
                    @include('events.partials.form')
                </x-form.form>
            </div>
        </div>
    </x-content>
</x-layouts.app>
