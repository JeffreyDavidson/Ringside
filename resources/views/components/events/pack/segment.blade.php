@props(['index', 'entry' => [], 'card'])
<div class="pack-entry" data-segment>
    <input type="hidden" name="segments[{{ $index }}][id]" value="{{ $entry['id'] ?? '' }}" data-segment-id />
    <div class="pack-timings">
        <x-events.pack.field
            :name="'segments['.$index.'][label]'"
            :label="__('event-pack.segment_name')"
            :value="$entry['label'] ?? null"
            :max="160"
        />
        <label class="pack-field"
            ><span>{{ __('event-pack.position') }}</span>
            <select name="segments[{{ $index }}][after_match_id]">
                <option value="">{{ __('event-pack.before_first') }}</option>
                @foreach ($card['rows'] as $row)
                    <option
                        value="{{ $row['id'] }}"
                        @selected((string) ($entry['after_match_id'] ?? '') === (string) $row['id'])
                    >
                        {{ __('event-pack.after_match', ['number' => $row['number']]) }}
                    </option>
                @endforeach
                @if (! empty($entry['after_match_id']) && ! in_array((int) $entry['after_match_id'], array_column($card['rows'], 'id'), true))
                    <option value="{{ $entry['after_match_id'] }}" selected>
                        {{ __('event-pack.missing_anchor') }}
                    </option>
                @endif
            </select>
        </label>
        <button type="button" data-remove-segment class="pack-button">{{ __('event-pack.remove_segment') }}</button>
    </div>
    <x-events.pack.timing :prefix="'segments['.$index.']'" :$entry />
</div>
