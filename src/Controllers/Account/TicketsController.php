<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;

final class TicketsController extends BaseController
{
    public function index(Request $request): Response
    {
        $user = User::currentModel();
        $tickets = Ticket::where('user_id = :u ORDER BY id DESC', ['u' => (int) $user->id]);
        return $this->view('account/tickets/index', ['tickets' => $tickets]);
    }

    public function create(Request $request): Response
    {
        return $this->view('account/tickets/create');
    }

    public function store(Request $request): Response
    {
        $user = User::currentModel();
        $ticket = new Ticket([
            'user_id' => (int) $user->id,
            'number' => 'TK-' . strtoupper(bin2hex(random_bytes(4))),
            'subject' => (string) $request->input('subject'),
            'priority' => (string) $request->input('priority', 'normal'),
            'status' => 'open',
        ]);
        $ticket->save();
        $reply = new TicketReply([
            'ticket_id' => (int) $ticket->id,
            'user_id' => (int) $user->id,
            'body' => (string) $request->input('message'),
            'is_staff' => 0,
        ]);
        $reply->save();
        return $this->redirect('/account/tickets/' . $ticket->id);
    }

    public function show(Request $request, int $id): Response
    {
        $user = User::currentModel();
        $ticket = Ticket::find($id);
        if (!$ticket || (int) $ticket->user_id !== (int) $user->id) {
            return $this->view('errors/404', [], 404);
        }
        $replies = TicketReply::where('ticket_id = :t ORDER BY id ASC', ['t' => (int) $id]);
        return $this->view('account/tickets/show', ['ticket' => $ticket, 'replies' => $replies]);
    }

    public function reply(Request $request, int $id): Response
    {
        $user = User::currentModel();
        $ticket = Ticket::find($id);
        if (!$ticket || (int) $ticket->user_id !== (int) $user->id) {
            return $this->view('errors/404', [], 404);
        }
        $reply = new TicketReply([
            'ticket_id' => (int) $id,
            'user_id' => (int) $user->id,
            'body' => (string) $request->input('message'),
            'is_staff' => 0,
        ]);
        $reply->save();
        $ticket->status = 'open';
        $ticket->save();
        return $this->redirect('/account/tickets/' . $id);
    }
}
