{{ $stable->name }}

@foreach ($stable->members as $member)
    {{ $member->name }}
@endforeach

