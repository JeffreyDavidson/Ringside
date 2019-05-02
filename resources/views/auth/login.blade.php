@extends('layouts.auth')

@section('content')
<div class="kt-login__body">

    <!--begin::Signin-->
    <div class="kt-login__form">
        <div class="kt-login__title">
            <h3>Sign In</h3>
        </div>

        <!--begin::Form-->
        <form class="kt-form" method="post" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <input class="form-control @error('email') is-invalid @enderror" type="text" placeholder="Email" name="email" autocomplete="off">
                @error('email')
                    <div id="email-error" class="error invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <input class="form-control @error('password') is-invalid @enderror" type="password" placeholder="Password" name="password">
                @error('password')
                    <div id="password-error" class="error invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!--begin::Action-->
            <div class="kt-login__actions">
                <button type="submit" id="kt_login_signin_submit" class="btn btn-primary btn-elevate kt-login__btn-primary">{{ __('Login') }}</button>
            </div>

            <!--end::Action-->
        </form>

        <!--end::Form-->

    </div>

    <!--end::Signin-->
</div>
@endsection
