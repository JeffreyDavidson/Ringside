<x-layouts.app>

    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>{{  $wrestler->name }}</x-page-heading>
            <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :url="route('wrestlers.index')" label="Wrestlers" />
            <x-breadcrumbs.separator />
            <x-breadcrumbs.item :label="$wrestler->name" />
        </x-toolbar>
    </x-slot>

    <x-card>
        <x-slot name="header">
            <div class="m-0 card-title">
                <h3 class="m-0 fw-bold">Wrestler Details</h3>
            </div>
            <x-button.primary :url="route('wrestlers.edit', $wrestler)" label="Edit Wrestler" />
        </x-slot>
        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Wrestler Name</label>
            <div class="col-lg-8">
                <span class="text-gray-800 fs-6">{{ $wrestler->name }}</span>
            </div>
        </div>
        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Height</label>
            <div class="col-lg-8">
                <span class="text-gray-800 fs-6">{{ floor($wrestler->height / 12) }}' {{ $wrestler->height % 12 }}"</span>
            </div>
        </div>
        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Weight</label>
            <div class="col-lg-8">
                <span class="text-gray-800 fs-6">{{ $wrestler->weight }} lbs.</span>
            </div>
        </div>
        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Hometown</label>
            <div class="col-lg-8">
                <span class="text-gray-800 fs-6">{{ $wrestler->hometown }}</span>
            </div>
        </div>
        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Signature Move</label>
            <div class="col-lg-8">
                <span class="text-gray-800 fs-6">{{ $wrestler->signature_move }}</span>
            </div>
        </div>
        <div class="row mb-7">
            <label class="col-lg-4 fw-semibold text-muted">Start Date</label>
            <div class="col-lg-8 fv-row">
                <span class="text-gray-800 fw-semibold fs-6">{{ $wrestler->employedAt?->toDateString() ?? 'No Start Date Set' }}</span>
            </div>
        </div>
        @if ($wrestler->isUnemployed())
            <x-notice
                title="This wrestler needs your attention!"
                description="This wrestler does not have a start date and needs to be employed."
            />
        @endif
    </x-card>
</x-layouts.app>
