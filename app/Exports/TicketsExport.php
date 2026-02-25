<?php

namespace App\Exports;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TicketsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Ticket::with('user', 'categories', 'labels', 'assignedToUser')
            ->when(auth()->user()->hasRole('user'), function (Builder $query) {
                $query->where('user_id', auth()->id());
            })
            ->get()
            ->map(function ($ticket) {
                return [
                    'Ticket ID'     => $ticket->id,
                    'Title'         => $ticket->title,
                    'Status'        => $ticket->status,
                    'Description'   => $ticket->message,
                    'Created By'    => $ticket->user->name ?? '-',
                    'Assigned To'   => $ticket->assignedToUser->name ?? '-',
                    'Categories'    => $ticket->categories->pluck('name')->implode(', '),
                    'Labels'        => $ticket->labels->pluck('name')->implode(', '),
                    'Created At'    => $ticket->created_at,
                    'Updated At'    => $ticket->updated_at,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Ticket ID',
            'Title',
            'Status',
            'Description',
            'Created By',
            'Assigned To',
            'Categories',
            'Labels',
            'Created At',
            'Updated At',
        ];
    }
}

