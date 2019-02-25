<table>
    <thead>
        <th>Name</th>
    </thead>
    <tbody>
    @foreach($managers as $manager)
        <tr>
            <td>{{ $manager->full_name }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
