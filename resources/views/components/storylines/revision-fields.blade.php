@props(['data'])
<dl class="mt-3 space-y-3">
    @foreach (['title' => 'name', 'premise' => 'premise', 'payoff' => 'payoff', 'heading' => 'heading', 'description' => 'description', 'status' => 'status', 'target_on' => 'target', 'occurred_on' => 'occurred', 'label' => 'participants', 'role' => 'role', 'joined_on' => 'joined', 'left_on' => 'left'] as $field => $label)
        @if (isset($data[$field]))
            <div>
                <dt class="text-ringside-muted text-sm">{{ __('storylines.'.$label) }}</dt>
                <dd class="mt-1 break-words whitespace-pre-line">
                    {{ $field === 'status' ? __('storylines.'.$data[$field]) : $data[$field] }}
                </dd>
            </div>
        @endif
    @endforeach
    @if (isset($data['source_context']))
        <div>
            <dt class="text-ringside-muted text-sm">{{ __('storylines.saved_context') }}</dt>
            <dd class="mt-1">
                {{ $data['source_context']['event_name'] ?? '' }} {{ $data['source_context']['event_date'] ?? '' }} {{ $data['source_context']['segment_label'] ?? $data['source_context']['match_label'] ?? '' }}
            </dd>
        </div>
    @endif
    @if (isset($data['cast']))
        <div>
            <dt class="text-ringside-muted text-sm">{{ __('storylines.cast') }}</dt>
            <dd class="mt-1">{{ collect($data['cast'])->pluck('label')->join(', ') }}</dd>
        </div>
    @endif
</dl>
