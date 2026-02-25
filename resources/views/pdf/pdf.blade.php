<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Report</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            vertical-align: top;
        }

        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
        }

        .small {
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>

<h1>Ticket Report</h1>

@foreach ($data as $item)
    @foreach ($item['ticket'] as $ticket)

        <table>
            <tr>
                <th width="25%">Ticket ID</th>
                <td>{{ $ticket->id }}</td>
            </tr>
            <tr>
                <th>Title</th>
                <td>{{ $ticket->title }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ ucfirst($ticket->status) }}</td>
            </tr>
            <tr>
                <th>Created By</th>
                <td>{{ $ticket->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Assigned To</th>
                <td>{{ $ticket->assignedToUser->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Categories</th>
                <td>
                    @foreach ($ticket->categories as $category)
                        {{ $category->name }}@if (!$loop->last), @endif
                    @endforeach
                </td>
            </tr>
            <tr>
                <th>Labels</th>
                <td>
                    @foreach ($ticket->labels as $label)
                        {{ $label->name }}@if (!$loop->last), @endif
                    @endforeach
                </td>
            </tr>
            <tr>
                <th>Description</th>
                <td>{{ $ticket->message }}</td>
            </tr>
            <tr>
                <th>Created At</th>
                <td>{{ $ticket->created_at->format('d M Y H:i') }}</td>
            </tr>
        </table>

        <div class="small">
            Generated on {{ now()->format('d M Y H:i') }}
        </div>

        {{-- Page break between tickets --}}
        <div style="page-break-after: always;"></div>

    @endforeach
@endforeach

</body>
</html>
