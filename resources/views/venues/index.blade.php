@extends('layouts.app')

@section('content-head')
<!-- begin:: Content Head -->
<div class="kt-subheader kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">Venues</h3>
        <a href="{{ route('venues.create') }}" class="btn btn-label-primary btn-bold btn-icon-h kt-margin-l-10">
			Add New
		</a>
    </div>
</div>

<!-- end:: Content Head -->
@endsection

@section('content')
<div class="kt-portlet kt-portlet--mobile">
    <div class="kt-portlet__head kt-portlet__head--lg"></div>
    <div class="kt-portlet__body">

        <!--begin: Datatable -->
        <div class="kt-datatable" id="kt_apps_venue_list_datatable"></div>
        {{-- @include('venues.partials.table') --}}

        <!--end: Datatable -->
    </div>
</div>
@endsection
