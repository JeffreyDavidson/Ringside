<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="Venues">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('venues.index')" label="Venues" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('venues.show', $venue)" :label="$venue->name" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item label="Edit" />
            <!--end::Item-->
        </x-toolbar>
    </x-slot>

    <div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Edit Venue Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('venues.update', $venue) }}">
                @method('patch')
                @csrf
                <div class="mb-10">
                    <x-form.inputs.text label="Name:" name="name" placeholder="Venue Name Here" value="{{ $venue->name }}" />
                </div>
                <div class="mb-10">
                    <div class="mb-5 row gx-10">
                        <div class="col-lg-8">
                            <x-form.inputs.text label="Street Address:" name="street_address" placeholder="Street Address Here" value="{{ $venue->street_address }}" />
                        </div>
                    </div>
                </div>
                <div class="mb-10">
                    <div class="mb-5 row gx-10">
                        <div class="col-lg-4">
                            <x-form.inputs.text label="City:" name="city" placeholder="Orlando" value="{{ $venue->city }}" />
                        </div>
                        <div class="col-lg-4">
                            <x-form.inputs.text label="State:" name="state" placeholder="Florida" value="{{ $venue->state }}" />
                        </div>
                        <div class="col-lg-4">
                            <x-form.inputs.text label="Zip:" name="zip" placeholder="12345" value="{{ $venue->zip }}" />
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
