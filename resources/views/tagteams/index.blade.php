<table>
    <thead>
        <th>Name</th>
    </thead>
    <tbody>
    @foreach($tagteams as $tagteam)
        <tr>
            <td>{{ $tagteam->name }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
