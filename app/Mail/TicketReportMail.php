<?php

namespace App\Mail;

use App\Exports\TicketsExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class TicketReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function build()
    {
        return $this->subject('Ticket Report')
            ->view('emails.ticket-report')
            ->attachData(
                Excel::raw(new TicketsExport, \Maatwebsite\Excel\Excel::XLSX),
                'tickets.xlsx',
                [
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            );

    }
}

