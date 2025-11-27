<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Comerciante</th>
            <th>IP</th>
            <th>User Agent</th>
            <th>Device</th>
            <th>Referer</th>
        </tr>
    </thead>
    <tbody>
        @foreach($metrics as $metric)
            <tr>
                <td>{{ $metric->created_at }}</td>
                <td>{{ $metric->merchant->rzsocial ?? $metric->merchant_external_id }}</td>
                <td>{{ $metric->ip }}</td>
                <td>{{ $metric->user_agent }}</td>
                <td>{{ $metric->device }}</td>
                <td>{{ $metric->referer }}</td>
            </tr>
        @endforeach
    </tbody>
</table>