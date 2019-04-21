@extends('layouts.app')

@section('content-head')
<!-- begin:: Content Head -->
<div class="kt-subheader kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">Venues</h3>
    </div>
</div>

<!-- end:: Content Head -->
@endsection

@section('content')
<div class="col-xl-12">
    <!--begin:: Widgets/Company Summary-->
    <div class="kt-portlet kt-portlet--height-fluid">
        <div class="kt-portlet__head">
            <div class="kt-portlet__head-label">
                <h3 class="kt-portlet__head-title">
                    Venue Summary
                </h3>
            </div>
        </div>
        <div class="kt-portlet__body">
            <div class="kt-widget13">
                <div class="kt-widget13__item">
                    <span class="kt-widget13__desc">
                        Venue Name
                    </span>
                    <span class="kt-widget13__text kt-widget13__text--bold">
                        {{ $venue->name }}.
                    </span>
                </div>
                <div class="kt-widget13__item">
                    <span class="kt-widget13__desc">
                        Venue Address:
                    </span>
                    <span class="kt-widget13__text">
                        {!! $venue->fullAddress !!}
                    </span>
                </div>
            </div>
        </div>
    </div>
    <!--end:: Widgets/Company Summary-->
</div>
@endsection
