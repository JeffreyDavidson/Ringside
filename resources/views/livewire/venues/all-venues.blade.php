<div class="card">
    @include('livewire.venues.partials.header')
    <div class="py-4 card-body">
        <x-data-table :collection="$venues">
            <thead>
                <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2 sorting_disabled" rowspan="1" colspan="1" style="width: 29.25px;" aria-label="">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" wire:model="selectPage">
                        </div>
                    </th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Zip Code</th>
                    <th class="text-end min-w-100px sorting_disabled">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($venues as $venue)
                    <tr>
                        <td>
                            <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="{{ $venue->id }}" wire:model="selected">
                            </div>
                        </td>
                        <td><a class="mb-1 text-gray-800 text-hover-primary" href="{{ route('venues.show', $venue) }}">{{ $venue->name }}</a></td>
                        <td>{{ $venue->address1 }}</td>
                        <td>{{ $venue->city }}</td>
                        <td>{{ $venue->state }}</td>
                        <td>{{ $venue->zip }}</td>
                        <td class="text-end">
                            @include('venues.partials.action-cell', [
                                'venue' => $venue,
                                'actions' => collect([
                                    'update',
                                    'delete',
                                ])
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">No matching records found</td>
                    </tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>

@if ($showDeleteModal)
<!-- Delete Venues Modal -->
<form wire:submit.prevent="deleteSelected">
    <x-modal.confirmation wire:model.defer="showDeleteModal">
        <x-slot name="title">Delete Venue</x-slot>

        <x-slot name="content">
            <div class="py-8 text-cool-gray-700">Are you sure you? This action is irreversible.</div>
        </x-slot>

        <x-slot name="footer">
            <x-button.secondary wire:click="$set('showDeleteModal', false)">Cancel</x-button.secondary>

            <x-button.primary type="submit">Delete</x-button.primary>
        </x-slot>
    </x-modal.confirmation>
</form>
@endif
