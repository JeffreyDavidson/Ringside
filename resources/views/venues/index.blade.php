<table>
    <thead>
        <th>Name</th>
    </thead>
    <tbody>
    @foreach($venues as $venue)
        <tr>
            <td>{{ $venue->name }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
