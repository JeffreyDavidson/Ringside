<x-layouts.app>
    <x-slot name="toolbar">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Create Venue
        </h2>
    </x-slot>

    <x-content>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Venue Form</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('venues.store') }}" method="post">
                    @csrf
                    @include('venues.partials.form')
                    <div class="row">
                        <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start">
                            <button type="submit" class="me-2 btn btn-primary">Submit</button>
                            <button type="reset" class="btn btn-secondary">Cancel</button>
                        </div>

                        <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
                            <a href="{{ route('venues.index') }}" class="btn btn-md btn-secondary">
                                <!--begin::Svg Icon | path: assets/media/icons/duotune/arrows/arr079.svg-->
                                <span class="svg-icon svg-icon-muted">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path opacity="0.5" d="M14.2657 11.4343L18.45 7.25C18.8642 6.83579 18.8642 6.16421 18.45 5.75C18.0358 5.33579 17.3642 5.33579 16.95 5.75L11.4071 11.2929C11.0166 11.6834 11.0166 12.3166 11.4071 12.7071L16.95 18.25C17.3642 18.6642 18.0358 18.6642 18.45 18.25C18.8642 17.8358 18.8642 17.1642 18.45 16.75L14.2657 12.5657C13.9533 12.2533 13.9533 11.7467 14.2657 11.4343Z" fill="black"/>
                                        <path d="M8.2657 11.4343L12.45 7.25C12.8642 6.83579 12.8642 6.16421 12.45 5.75C12.0358 5.33579 11.3642 5.33579 10.95 5.75L5.40712 11.2929C5.01659 11.6834 5.01659 12.3166 5.40712 12.7071L10.95 18.25C11.3642 18.6642 12.0358 18.6642 12.45 18.25C12.8642 17.8358 12.8642 17.1642 12.45 16.75L8.2657 12.5657C7.95328 12.2533 7.95328 11.7467 8.2657 11.4343Z" fill="black"/>
                                    </svg>
                                </span>
                                <!--end::Svg Icon-->
                                Back to Venues
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </x-content>
</x-layouts.app>
