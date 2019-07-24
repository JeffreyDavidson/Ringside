@extends('layouts.app')

@push('scripts-after')
    <script src="{{ mix('js/managers/index.js') }}"></script>
@endpush

@section('content-head')
<!-- begin:: Content Head -->
<div class="kt-subheader kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">Managers</h3>
        <span class="kt-subheader__separator kt-subheader__separator--v"></span>
        @include('partials.search')
        @include('managers.partials.filters')
    </div>
    <div class="kt-subheader__toolbar">
        <a href="{{ route('managers.create') }}"
            class="btn btn-label-brand btn-bold">
            Add Manager
        </a>
    </div>
</div>

<!-- end:: Content Head -->
@endsection

@section('content')
<div class="kt-portlet kt-portlet--mobile">
    <div class="kt-portlet__body p-0">

        <!--begin: Datatable -->
        <table id="managers_table" data-table="managers.index" class="table table-hover"></table>

        <!--end: Datatable -->
    </div>
</div>
@endsection
