<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use App\Http\Requests\StoreQueueTicketRequest;
use App\Http\Requests\UpdateQueueTicketStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class QueueTicketController extends Controller
{
    // Manage Queue: today's active tickets
    public function index(): JsonResponse
{
    $session = QueueSession::today();

    $tickets = $session->tickets()
        ->with(['barber', 'service'])
        ->whereIn('status', ['in_queue', 'serving'])
        ->orderBy('queue_number')
        ->get();

    return response()->json($tickets);
}

    // Queue History: completed/canceled tickets, most recent first
    public function history(Request $request): JsonResponse
    {
        $tickets = QueueTicket::with(['barber', 'service', 'session'])
            ->whereIn('status', ['completed', 'canceled'])
            ->when($request->date, fn ($q) => $q->whereHas(
                'session',
                fn ($s) => $s->whereDate('session_date', $request->date)
            ))
            ->latest('finished_at')
            ->paginate(20);

        return response()->json($tickets);
    }

    public function store(StoreQueueTicketRequest $request): JsonResponse
    {
        $session = QueueSession::today();

        $nextNumber = $session->tickets()->max('queue_number') + 1;

        $ticket = $session->tickets()->create($request->validated() + [
            'queue_number' => $nextNumber,
            'status' => 'in_queue',
            'joined_at' => now(),
        ]);

        return response()->json([
            'ticket' => $ticket,
            'estimated_wait_minutes' => $this->estimateWait($session),
        ], 201);
    }

    public function updateStatus(UpdateQueueTicketStatusRequest $request, QueueTicket $ticket): JsonResponse
    {
        $data = ['status' => $request->status];

        if ($request->status === 'serving' && !$ticket->served_at) {
            $data['served_at'] = now();
        }

        if (in_array($request->status, ['completed', 'canceled']) && !$ticket->finished_at) {
            $data['finished_at'] = now();
        }

        $ticket->update($data);

        return response()->json($ticket->fresh());
    }

    private function estimateWait(QueueSession $session): int
    {
        $waiting = $session->tickets()->where('status', 'in_queue')->count();
        return $waiting * 15; // simple heuristic; swap for a real average-service-time calc
    }
    public function sendSms(Request $request, QueueTicket $ticket): JsonResponse
{
    $request->validate(['message_template_id' => 'nullable|exists:message_templates,id']);

    $template = $request->message_template_id
        ? MessageTemplate::find($request->message_template_id)
        : null;

    $body = $template?->message_body
        ?? "Hi {$ticket->customer_name}, your queue number {$ticket->formatted_queue_number} is coming up.";

    $log = $ticket->smsLogs()->create([
        'message_template_id' => $template?->id,
        'phone' => $ticket->customer_phone,
        'message_body' => $body,
        'status' => 'pending',
    ]);


    // Dispatch to your SMS provider job/queue here, e.g.:
    // SendSmsJob::dispatch($log);

    return response()->json($log, 201);
}
public function lookup(Request $request): JsonResponse
{
    $request->validate([
        'queue_number' => ['required', 'integer'],
    ]);

    $session = QueueSession::today();

    $ticket = $session->tickets()
        ->with(['barber', 'service'])
        ->where('queue_number', $request->queue_number)
        ->first();

    if (!$ticket) {
        return response()->json(['message' => 'No ticket found with that queue number today.'], 404);
    }

    $position = null;
    if (in_array($ticket->status, ['in_queue', 'serving'])) {
        $position = $session->tickets()
            ->whereIn('status', ['in_queue', 'serving'])
            ->where('queue_number', '<=', $ticket->queue_number)
            ->count();
    }

    return response()->json([
        'ticket' => $ticket,
        'position' => $position,
        'estimated_wait_minutes' => $position ? max(0, ($position - 1) * 15) : null,
    ]);
}
}
