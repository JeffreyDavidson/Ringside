@props(['method', 'action', 'backTo', 'resource'])

<form class="kt-form" method="POST" action="{!! $action !!}" {{ $attributes }}>
    @csrf
    @if ($attributes->has('method'))
        @method($method)
    @endif

    {{ $slot }}

    <div class="row">
        <div class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start">
            <button type="submit" class="me-2 btn btn-primary">Submit</button>
            <button type="reset" class="btn btn-secondary">Cancel</button>
        </div>

        <div class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end">
            <x-buttons.back-to resource="{{ $resource }}" route="{{ $backTo }}" />
        </div>
    </div>
</form>
