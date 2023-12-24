<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Create Venue</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('venues.index')" label="Venues" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item label="Create" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Create A New Venue Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('venues.store') }}">
                @csrf
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Name:"
                        name="name"
                        placeholder="Venue Name Here"
                        :value="old('name')"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Street Address:"
                        name="street_address"
                        placeholder="Street Address Here"
                        :value="old('street_address')"
                    />
                </div>
                <div class="mb-10">
                    <div class="mb-5 row gx-10">
                        <div class="col-lg-4">
                            <x-form.inputs.text
                                label="City:"
                                name="city"
                                placeholder="Orlando"
                                :value="old('city')"
                            />
                        </div>
                        <div class="col-lg-4">
                            <x-form.inputs.text
                                label="State:"
                                name="state"
                                placeholder="Florida"
                                :value="old('state')"
                            />
                        </div>
                        <div class="col-lg-4">
                            <x-form.inputs.text
                                label="Zip:"
                                name="zip"
                                placeholder="12345"
                                :value="old('zip')"
                            />
                        </div>
                    </div>
                </div>
        </div>
        <div class="card-footer">
            <x-form.buttons.submit />
            <x-form.buttons.reset />
        </div>
        </form>
    </div>
</x-layouts.app>
