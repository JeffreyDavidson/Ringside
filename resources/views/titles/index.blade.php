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
