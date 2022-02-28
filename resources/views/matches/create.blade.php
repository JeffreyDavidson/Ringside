<x-layouts.app>
    <x-slot name="toolbar">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Create Match
        </h2>
    </x-slot>

    <x-content>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create Match for {{ $event->name }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('events.matches.store', $event) }}" method="post">
                    @csrf
                    @include('matches.partials.form')
                    <div class="row">
                        <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-start">
                            <a href="{{ route('events.matches.index', $event) }}" class="btn btn-md btn-secondary">
                                <x-icons.arrow />
                                Back to Matches
                            </a>
                        </div>

                        <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-end">
                            <button type="reset" class="me-2 btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </x-content>
</x-layouts.app>
