<x-layouts.app>
    <x-slot name="toolbar">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Edit Title
        </h2>
    </x-slot>

    <x-content>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Title Form</h3>
            </div>
            <div class="card-body">
                <x-form.form
                    action="{{ route('titles.update', $title) }}"
                    method="PATCH"
                    backTo="{{ route('titles.index') }}"
                    resource="Titles"
                >
                    @include('titles.partials.form')
                </x-form.form>
            </div>
        </div>
    </x-content>
</x-layouts.app>
