<table class="table table-striped table-bordered table-hover" id="resourceTable">
    <thead>
        <tr>
            <th>Venue Name</th>
            <th>Address</th>
            <th>City</th>
            <th>State</th>
            <th>Zip Code</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($venues as $venue)
        <tr>
            <td>{{ $venue->name }}</td>
            <td>{{ $venue->address1 }}</td>
            <td>{{ $venue->city }}</td>
            <td>{{ $venue->state }}</td>
            <td>{{ $venue->zip }}</td>
            <td nowrap>
                <a href="{{ route('venues.edit', $venue) }}" class="btn btn-sm btn-clean btn-icon btn-icon-md" title="Edit">
                    <i class="la la-edit"></i>
                </a>
                <a href="{{ route('venues.show', $venue) }}" class="btn btn-sm btn-clean btn-icon btn-icon-md" title="View">
                    <i class="la la-eye"></i>
                </a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6">There are currently 0 venues in the system.</td>
        </tr>
    @endforelse
    </tbody>
</table>
