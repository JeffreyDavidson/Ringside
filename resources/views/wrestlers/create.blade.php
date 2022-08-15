<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar title="Wrestlers">
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('wrestlers.index')" label="Wrestlers" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item label="Create" />
        </x-toolbar>
    </x-slot>

    <div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Create A New Wrestler Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('wrestlers.store') }}">
                @csrf
                <div class="mb-10">
                    <x-form.inputs.text label="Name:" name="name" placeholder="Wrestler Name Here" />
                </div>
                <div class="mb-10">
                    <div class="mb-5 row gx-10">
                        <div class="col-lg-3">
                            <x-form.inputs.number label="Height (Feet):" name="feet" placeholder="6" />
                        </div>
                        <div class="col-lg-3">
                            <x-form.inputs.number label="Height (Inches):" name="inches" placeholder="2" max="11" />
                        </div>
                        <div class="col-lg-6">
                            <x-form.inputs.number label="Weight:" name="weight" placeholder="220" />
                        </div>
                    </div>
                </div>
                <div class="mb-10">
                    <x-form.inputs.text label="Hometown:" name="hometown" placeholder="Orlando, FL" />
                </div>
                <div class="mb-10">
                    <x-form.inputs.text label="Signature Move:" name="signature_move" placeholder="This Amazing Finisher" />
                </div>
                <div class="mb-10">
                    <x-form.inputs.date label="Start Date:" name="start_date" />
                </div>
        </div>
        <div class="card-footer">
            <x-form.buttons.submit />
            <x-form.buttons.reset />
        </div>
        </form>
    </div>
</x-layouts.app>
