@extends('layouts.app')

@section('content-head')
<!-- begin:: Content Head -->
<div class="kt-subheader kt-grid__item" id="kt_subheader">
    <div class="kt-subheader__main">
        <h3 class="kt-subheader__title">Events</h3>
    </div>
</div>

<!-- end:: Content Head -->
@endsection

@section('content')
<table>
    <thead>
        <th>Name</th>
    </thead>
    <tbody>
    @foreach($titles as $title)
        <tr>
            <td>{{ $title->name }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection

