@extends('layouts.app')

@push('scripts-after')
    <script src="{{ mix('js/stables/index.js') }}"></script>
@endpush

@section('content-head')
<!-- begin:: Content Head -->
<div class="kt-subheader kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">Stables</h3>
        <span class="kt-subheader__separator kt-subheader__separator--v"></span>
        @include('partials.search')
        @include('stables.partials.filters')
    </div>
    <div class="kt-subheader__toolbar">
        <a href="{{ route('stables.create') }}"
            class="btn btn-label-brand btn-bold">
            Add Stable
        </a>
    </div>
</div>

<!-- end:: Content Head -->
@endsection

@section('content')
<div class="kt-portlet kt-portlet--mobile">
    <div class="kt-portlet__body p-0">

        <!--begin: Datatable -->
        <table id="stables_table" data-table="stables.index" class="table table-hover"></table>

        <!--end: Datatable -->
    </div>
</div>
@endsection
