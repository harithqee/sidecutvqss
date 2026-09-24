<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use App\Http\Requests\StoreQueueTicketRequest;
use App\Http\Requests\UpdateQueueTicketStatusRequest;
use App\Services\TextBeeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QueueTicketController extends Controller
{
    public function __construct(protected TextBeeService $textBee) {}

    /**
     * Manage Queue: today's active tickets only (in_queue or serving).
     */
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

    /**
     * Queue History: completed/canceled tickets, most recent first.
     */
    public function history(Request $request): JsonResponse
    {
        $tickets = QueueTicket::with(['barber', 'service', 'session'])
            ->whereIn('status', ['completed', 'canceled'])
            ->when($request->date, fn ($q) => $q->whereHas(
                'session',
                fn ($s) => $s->whereDate('session_date', $request->date)
            ))
            ->when($request->from && $request->to, fn ($q) => $q->whereHas(
                'session',
                fn ($s) => $s->whereBetween('session_date', [$request->from, $request->to])
            ))
            ->latest('finished_at')
            ->paginate(20);

        return response()->json($tickets);
    }

    /**
     * Customer joins the queue. Fires the "Customer Join the Queue"
     * SMS template (ID 1) automatically on success.
     */
    public function store(StoreQueueTicketRequest $request): JsonResponse
    {
        $session = QueueSession::today();
        $nextNumber = $session->tickets()->max('queue_number') + 1;

        $ticket = $session->tickets()->create($request->validated() + [
            'queue_number' => $nextNumber,
            'status' => 'in_queue',
            'joined_at' => now(),
        ]);

        $estimatedWait = $this->estimateWait($session);

        try {
    $this->textBee->sendById(1, $ticket->customer_phone, [
        'customerName' => $ticket->customer_name,
        'queueNumber' => $ticket->queue_number,
        'waitTime' => $estimatedWait,
    ]);
} catch (\Throwable $e) {
    \Log::warning('TextBee send failed on queue join: ' . $e->getMessage());
}

        return response()->json([
            'ticket' => $ticket,
            'estimated_wait_minutes' => $estimatedWait,
        ], 201);
    }

    /**
     * Update a ticket's status: in_queue -> serving -> completed/canceled.
     */
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

    /**
     * Manually triggered "Send Message" button in Manage Queue.
     * Fires the "In-Queue Status Update" SMS template (ID 2).
     */
    public function sendSms(Request $request, QueueTicket $ticket): JsonResponse
    {
        $position = $ticket->session->tickets()
            ->whereIn('status', ['in_queue', 'serving'])
            ->where('queue_number', '<=', $ticket->queue_number)
            ->count();

        try {
    $result = $this->textBee->sendById(2, $ticket->customer_phone, [
        'customerName' => $ticket->customer_name,
        'position' => $position,
    ]);
} catch (\Throwable $e) {
    return response()->json(['message' => 'Could not send SMS: ' . $e->getMessage()], 502);
}

        $log = $ticket->smsLogs()->create([
            'phone' => $ticket->customer_phone,
            'message_body' => $result['message'],
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json($log, 201);
    }

    /**
     * Customer-facing: look up a ticket's status by queue number.
     */
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

    /**
     * Simple heuristic wait-time estimate based on how many are currently waiting.
     */
    private function estimateWait(QueueSession $session): int
    {
        $waiting = $session->tickets()->where('status', 'in_queue')->count();
        return $waiting * 15;
    }
}