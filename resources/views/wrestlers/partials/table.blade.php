<table class="table table-striped table-bordered table-hover" id="resource-table">
    <thead>
        <tr>
            <th>Wrestler ID</th>
            <th>Wrestler Name</th>
            <th>Hometown</th>
            <th>Hired Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($wrestlers as $wrestler)
        <tr>
            <td>{{ $wrestler->id }}</td>
            <td>{{ $wrestler->name }}</td>
            <td>{{ $wrestler->hometown }}</td>
            <td>{{ $wrestler->formatted_hired_at }}</td>
            <td>{{ $wrestler->status }}</td>
            @include('layouts.partials.table.actions')
        </tr>
    @empty
        <tr>
            <td colspan="5">There are currently 0 wrestlers in the system.</td>
        </tr>
    @endforelse
    </tbody>
</table>
