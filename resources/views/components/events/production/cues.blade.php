@props(['cues', 'assignments' => []])
@foreach ($cues as $cue)
    <div class="production-cue border-border mt-4 border-t py-4 break-words">
        <strong>{{ $cue['subject'] }}</strong>
        <p class="mt-2">
            <strong>{{ $cue['trigger'] }}</strong> ·
            <span class="production-preserve whitespace-pre-wrap">{{ $cue['instruction'] }}</span>
        </p>
        @if ($cue['owner_id'])
            <p class="mt-2">
                {{ __('production.owner') }}: {{ $cue['owner_name'] }}
                @if ($cue['owner_role']) ·{{ __('production.roles.'.$cue['owner_role']) }} @endif
                · {{ $cue['owner_station'] }}
                @if (! in_array($cue['owner_id'], array_column($assignments, 'id'), true))
                    <strong>{{ __('production.removed_owner') }}</strong>
                @endif
            </p>
        @else
            <p class="mt-2 font-semibold">{{ __('production.unassigned') }}</p>
        @endif
        @if ($cue['role'])
            <p class="mt-2">{{ __('production.target_role') }}: {{ __('production.roles.'.$cue['role']) }}</p>
        @endif
        @if ($cue['pronunciation'])
            <p class="mt-2">{{ __('production.pronunciation') }}: {{ $cue['pronunciation'] }}</p>
        @endif
        @if ($cue['track'])
            <p class="mt-2">{{ __('production.track') }}: {{ $cue['track'] }}</p>
        @endif
        @if ($cue['url'])
            <p class="mt-2">
                <a
                    class="underline underline-offset-4"
                    href="{{ $cue['url'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                >{{ $cue['url'] }}</a>
            </p>
        @endif
    </div>
@endforeach
