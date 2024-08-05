@aware(['match'])

<p>
    {{ str('Referee')->plural($match->referees->count()) }}:

    @foreach ($match->referees as $referee)
        <x-route-link :route="route('referees.show', $referee)" label="{{ $referee->full_name }}"/>

        @if (! $loop->last)
            @php echo " & " @endphp
        @endif
    @endforeach
</p>
