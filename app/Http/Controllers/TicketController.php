<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use App\Http\Requests\TicketRequest;
use Coderflex\LaravelTicket\Models\Label;
use Illuminate\Database\Eloquent\Builder;
use Coderflex\LaravelTicket\Models\Category;
use App\Notifications\AssignedTicketNotification;
use App\Notifications\NewTicketCreatedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\TicketsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Mail\TicketReportMail;
use App\Mail\TicketClosedMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;


class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = Ticket::with('user', 'categories', 'labels', 'assignedToUser')
            ->when($request->has('status'), function (Builder $query) use ($request) {
                return $query->where('status', $request->input('status'));
            })
            ->when($request->has('priority'), function (Builder $query) use ($request) {
                return $query->withPriority($request->input('priority'));
            })
            ->when($request->has('category'), function (Builder $query) use ($request) {
                return $query->whereRelation('categories', 'id', $request->input('category'));
            })
            ->when(auth()->user()->hasRole('agent'), function (Builder $query) {
                $query->where('assigned_to', auth()->user()->id);
            })
            ->when(auth()->user()->hasRole('user'), function (Builder $query) {
                $query->where('user_id', auth()->user()->id);
            })
            ->latest()
            ->paginate();

        return view('tickets.index', compact('tickets'));
    }
    

    public function create(): View
    {
        $labels = Label::visible()->pluck('name', 'id');

        $categories = Category::visible()->pluck('name', 'id');

        $users = User::role('agent')->orderBy('name')->pluck('name', 'id');

        return view('tickets.create', compact('labels', 'categories', 'users'));
    }

    public function store(TicketRequest $request)
    {
        $ticket = auth()->user()->tickets()->create($request->only('title', 'message', 'status', 'priority'));

        $ticket->attachCategories($request->input('categories'));

        $ticket->attachLabels($request->input('labels'));

        if ($request->input('assigned_to')) {
            $ticket->assignTo($request->input('assigned_to'));
            User::find($request->input('assigned_to'))->notify(new AssignedTicketNotification($ticket));
        } else {
            User::role('admin')
                ->each(fn ($user) => $user->notify(new NewTicketCreatedNotification($ticket)));
        }

        if (!is_null($request->input('attachments'))) {
            foreach ($request->input('attachments') as $file) {
                $ticket->addMediaFromDisk($file, 'public')->toMediaCollection('tickets_attachments');
            }
        }

        return to_route('tickets.index');
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['media', 'messages' => fn ($query) => $query->latest()]);

        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket): View
    {
        $this->authorize('update', $ticket);

        $labels = Label::visible()->pluck('name', 'id');

        $categories = Category::visible()->pluck('name', 'id');

        $users = User::role('agent')->orderBy('name')->pluck('name', 'id');

        return view('tickets.edit', compact('ticket', 'labels', 'categories', 'users'));
    }

    public function update(TicketRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        // Track original status
        $oldStatus = $ticket->status;

        $ticket->update($request->only(
            'title',
            'message',
            'status',
            'priority',
            'assigned_to'
        ));

        $ticket->syncCategories($request->input('categories'));
        $ticket->syncLabels($request->input('labels'));

        if ($ticket->wasChanged('assigned_to')) {
            User::find($request->input('assigned_to'))
                ?->notify(new AssignedTicketNotification($ticket));
        }

        // ✅ Send email ONLY when status changes to closed
        if (
            $oldStatus !== 'closed' &&
            $ticket->status === 'closed'
        ) {
            Mail::to($ticket->user->email)
                ->queue(new TicketClosedMail($ticket));
        }

        // Attachments (safe check)
        if ($request->filled('attachments')) {
            foreach ($request->input('attachments') as $file) {
                if (!is_null($file)) {
                    $ticket->addMediaFromDisk($file, 'public')
                        ->toMediaCollection('tickets_attachments');
                }
            }
        }

        return to_route('tickets.index');
    }

    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return to_route('tickets.index');
    }

    public function upload(Request $request)
    {
        $path = [];

        if ($request->file('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tmp', 'public');
            }
        }

        return $path;
    }

    public function close(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $ticket->close();

        return to_route('tickets.show', $ticket);
    }

    public function reopen(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $ticket->reopen();

        return to_route('tickets.show', $ticket);
    }

    public function archive(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $ticket->archive();

        return to_route('tickets.show', $ticket);
    }

 
    public function download() {

        $ticket = Ticket::with('user', 'categories', 'labels', 'assignedToUser')
            ->when(auth()->user()->hasRole('user'), function (Builder $query) {
                $query->where('user_id', auth()->user()->id);
            })
            ->get();
            
        $data = [
            [
                'ticket' => $ticket,
            ]
        ];
    
        $pdf = Pdf::loadView('pdf/pdf', ['data' => $data]);
    
        return $pdf->download();
    }

    public function downloadExcel(){

        $date = Carbon::now()->format('Y-m-d');

        return Excel::download(new TicketsExport, 'tickets-'.$date.'.xlsx');
    }

    public function emailExcel()
    {
        Mail::to('mohdalif@assar.com.my')
            ->send(new TicketReportMail());

        return back()->with('success', 'Ticket report emailed successfully.');
    }
}
