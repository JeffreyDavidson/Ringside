<table>
    <thead>
        <th>Event Name</th>
    </thead>
    <tbody>
        @foreach($events as $event)
            <tr>
                <td>{{ $event->name }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
