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

                <div class="row mb-7">
                    <label class="col-lg-4 fw-bold text-muted">Date Introduced</label>

                    <div class="col-lg-8">
                        <span class="text-gray-800 fw-bolder fs-6">{{ $title->activatedAt->toDateString() }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="pt-3 mb-5 card card-flush mb-xl-10">
            <div class="card-header">
                <div class="card-title">
                    <h2>Championships</h2>
                </div>
            </div>
            <div class="pt-2 card-body">
                <x-data-table :collection="$title->championships()->paginate()">
                    <thead class="border-gray-200 border-bottom fs-7 text-uppercase fw-bolder">
                        <th>New Champion</th>
                        <th>Previous Champion</th>
                        <th>Event Name</th>
                        <th>Event Date</th>
                    </thead>
                    <tbody>
                        @forelse($title->championships as $championship)
                            <tr>
                                <td>{{ $championship->champion->name }}</td>
                                <td>{{ $championship->previousChampion?->name ?? 'First Champion' }}</td>
                                <td>{{ $championship->match->event->name }}</td>
                                <td>{{ $championship->match->event->date->toDateString() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">Title has not been won.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-data-table>
            </div>
        </div>
    </x-content>
</x-layouts.app>
