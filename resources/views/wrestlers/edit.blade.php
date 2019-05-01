@extends('layouts.app')

@section('content-head')
<!-- begin:: Content Head -->
<div class="kt-subheader kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">Wrestlers</h3>
    </div>
</div>

<!-- end:: Content Head -->
@endsection

@section('content')
<!--begin::Portlet-->
<div class="kt-portlet">
    <div class="kt-portlet__head">
        <div class="kt-portlet__head-label">
            <h3 class="kt-portlet__head-title">
                Edit Wrestler Form
            </h3>
        </div>
        <div class="kt-portlet__head-toolbar">
            <div class="kt-portlet__head-actions">
                <a href="{{ route('wrestlers.index') }}" class="btn btn-brand btn-elevate btn-bold">
                    <i class="la la-angle-double-left"></i>
                    Back to Wrestlers
                </a>
            </div>
        </div>
    </div>

    <!--begin::Form-->
    <form class="kt-form" method="post" action="{{ route('wrestlers.update', $wrestler) }}">
        @csrf
        @method('PATCH')
        <div class="kt-portlet__body">
            <div class="kt-section kt-section--first">
                @include('wrestlers.partials.form')
            </div>
        </div>
        <div class="kt-portlet__foot">
            <div class="kt-form__actions">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="reset" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </form>

     <!--end::Form-->
</div>

<!--end::Portlet-->
@endsection
