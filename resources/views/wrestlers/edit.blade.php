<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>Wrestlers</x-page-heading>
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('wrestlers.index')" label="Wrestlers" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('wrestlers.show', $wrestler)" :label="$wrestler->name" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item label="Edit" />
        </x-toolbar>
    </x-slot>

    <div class="shadow-sm card">
        <div class="card-header">
            <h3 class="card-title">Edit Wrestler Form</h3>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('wrestlers.update', $wrestler) }}">
                @method('patch')
                @csrf
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Name:"
                        name="name"
                        placeholder="Wrestler Name Here"
                        :value="old('name', $wrestler->name)"
                    />
                </div>
                <div class="mb-10">
                    <div class="mb-5 row gx-10">
                        <div class="col-lg-3">
                            <x-form.inputs.number
                                label="Height (Feet):"
                                name="feet"
                                placeholder="6"
                                max="8"
                                :value="old('feet', floor($wrestler->height / 12))"
                            />
                        </div>
                        <div class="col-lg-3">
                            <x-form.inputs.number
                                label="Height (Inches):"
                                name="inches"
                                placeholder="2"
                                max="11"
                                :value="old('inches', $wrestler->height % 12)"
                            />
                        </div>
                        <div class="col-lg-6">
                            <x-form.inputs.number
                                label="Weight:"
                                name="weight"
                                placeholder="220"
                                :value="old('weight', $wrestler->weight)"
                            />
                        </div>
                    </div>
                </div>
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Hometown:"
                        name="hometown"
                        placeholder="Orlando, FL"
                        :value="old('hometown', $wrestler->hometown)"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.text
                        label="Signature Move:"
                        name="signature_move"
                        placeholder="This Amazing Finisher"
                        :value="old('signature_move', $wrestler->signature_move)"
                    />
                </div>
                <div class="mb-10">
                    <x-form.inputs.date
                        label="Start Date:"
                        name="start_date"
                        :value="old('start_date', $wrestler->started_at?->format('Y-m-d'))"
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
