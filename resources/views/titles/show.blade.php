<x-layouts.app>
    <x-slot name="toolbar">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ $title->name }} Page
        </h2>
    </x-slot>

    <x-content>
        @empty($title->activated_at)
            <div class="alert alert-warning" role="alert">
                <strong>Warning!</strong>&nbsp;This title is not activated!
            </div>
        @endempty

        <div class="mb-5 card mb-xl-10" id="kt_profile_details_view">
            <div class="card-header">
                <div class="m-0 card-title">
                    <h3 class="m-0 fw-bolder">{{ $title->name }} Details</h3>
                </div>
                <a href="{{ route('titles.edit', $title) }}" class="btn btn-primary align-self-center">Edit Details</a>
            </div>

            <div class="card-body p-9">
                <div class="row mb-7">
                    <label class="col-lg-4 fw-bold text-muted">Name</label>

                    <div class="col-lg-8">
                        <span class="text-gray-800 fw-bolder fs-6">{{ $title->name }}</span>
                    </div>
                </div>

                @isset($title->activatedAt)
                    <div class="row mb-7">
                        <label class="col-lg-4 fw-bold text-muted">Date Introduced</label>

                        <div class="col-lg-8">
                            <span class="text-gray-800 fw-bolder fs-6">{{ $title->activatedAt->toDateString() }}</span>
                        </div>
                    </div>
                @endisset
            </div>
        </div>
        <livewire:titles.title-championships-list :title="$title" />
    </x-content>
</x-layouts.app>
