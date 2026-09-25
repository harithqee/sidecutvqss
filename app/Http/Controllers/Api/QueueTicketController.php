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

        $this->sendAndLogSms($ticket, 1, [
            'customerName' => $ticket->customer_name,
            'queueNumber' => $ticket->queue_number,
            'waitTime' => $estimatedWait,
        ]);

        return response()->json([
            'ticket' => $ticket,
            'estimated_wait_minutes' => $estimatedWait,
        ], 201);
    }

    /**
     * Update a ticket's status: in_queue -> serving -> completed/canceled.
     * Fires the "Service Complete" SMS template (ID 3) when moving to completed.
     */
    public function updateStatus(UpdateQueueTicketStatusRequest $request, QueueTicket $ticket): JsonResponse
    {
        $data = ['status' => $request->status];
        $wasNotCompleted = $ticket->status !== 'completed';

        if ($request->status === 'serving' && !$ticket->served_at) {
            $data['served_at'] = now();
        }

        if (in_array($request->status, ['completed', 'canceled']) && !$ticket->finished_at) {
            $data['finished_at'] = now();
        }

        $ticket->update($data);

        if ($request->status === 'completed' && $wasNotCompleted) {
            $this->sendAndLogSms($ticket, 3, [
                'customerName' => $ticket->customer_name,
            ]);
        }

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

        $result = $this->sendAndLogSms($ticket, 2, [
            'customerName' => $ticket->customer_name,
            'position' => $position,
        ]);

        if (!$result) {
            return response()->json(['message' => 'Could not send SMS.'], 502);
        }

        return response()->json(['message' => 'sent', 'text' => $result['message']], 201);
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
     * Sends an SMS via a template and logs the attempt (success or failure)
     * to sms_logs, so every automated and manual send is auditable.
     */
    private function sendAndLogSms(QueueTicket $ticket, int $templateId, array $variables): ?array
    {
        try {
            $result = $this->textBee->sendById($templateId, $ticket->customer_phone, $variables);

            $ticket->smsLogs()->create([
                'message_template_id' => $templateId,
                'phone' => $ticket->customer_phone,
                'message_body' => $result['message'],
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $ticket->smsLogs()->create([
                'message_template_id' => $templateId,
                'phone' => $ticket->customer_phone,
                'message_body' => '',
                'status' => 'failed',
                'sent_at' => null,
            ]);

            \Log::warning("TextBee send failed (template {$templateId}): " . $e->getMessage());
            return null;
        }
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