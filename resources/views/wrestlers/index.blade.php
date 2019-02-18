<table>
    <thead>
        <th>Name</th>
    </thead>
    <tbody>
    @foreach($wrestlers as $wrestler)
        <tr>
            <td>{{ $wrestler->name }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
