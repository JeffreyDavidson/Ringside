<table>
    <thead>
        <th>Name</th>
    </thead>
    <tbody>
    @foreach($stables as $stable)
        <tr>
            <td>{{ $stable->name }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
