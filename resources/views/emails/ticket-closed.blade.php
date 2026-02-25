<!DOCTYPE html>
<html>
<body>
    <p>Hi {{ $ticket->user->name }},</p>

    <p>Your ticket <strong>#{{ $ticket->id }}</strong> has been <strong>closed</strong>.</p>

    <p><strong>Title:</strong> {{ $ticket->title }}</p>
    <p><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</p>

    <p>If you have further issues, feel free to open a new ticket.</p>

    <p>
        Regards,<br>
        {{ config('app.name') }}
    </p>
</body>
</html>
