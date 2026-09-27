<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use App\Models\Barber;
use App\Models\Service;
use App\Http\Requests\StoreQueueTicketRequest;
use App\Http\Requests\UpdateQueueTicketStatusRequest;
use App\Services\TextBeeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\DB;

class QueueTicketController extends Controller
{
    public function __construct(protected TextBeeService $textBee) {}

    /**
     * Manage Queue: today's active tickets only (in_queue or serving).
     */
    public function index(): JsonResponse
    {
        if (!Barber::where('is_active', true)->exists()) {
            return response()->json([]);
        }

        $session = QueueSession::today();

        $tickets = $session->tickets()
            ->with(['barber', 'service'])
            ->whereIn('status', ['in_queue', 'serving'])
            ->orderBy('queue_number')
            ->get();

        return response()->json($tickets);
    }

    /** Public live board data for today's queue. */
    public function callingBoard(): JsonResponse
    {
        if (!Barber::where('is_active', true)->exists()) {
            return response()->json([
                'queue_active' => false,
                'current_call' => null,
                'recent_calls' => [],
                'upcoming' => [],
                'waiting_count' => 0,
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        $session = QueueSession::whereDate('session_date', today())->first();

        if (!$session) {
            return response()->json([
                'queue_active' => true,
                'current_call' => null,
                'recent_calls' => [],
                'upcoming' => [],
                'waiting_count' => 0,
                'updated_at' => now()->toIso8601String(),
            ]);
        }

        $activeTickets = $session->tickets()
            ->with(['barber:id,name', 'service:id,name'])
            ->whereIn('status', ['in_queue', 'serving'])
            ->orderBy('queue_number')
            ->get();

        $mapTicket = static fn (QueueTicket $ticket) => [
            'id' => $ticket->id,
            'queue_number' => $ticket->queue_number,
            'status' => $ticket->status,
            'barber' => $ticket->barber?->name,
            'service' => $ticket->service?->name,
            'called_at' => $ticket->called_at?->toIso8601String(),
            'call_version' => $ticket->call_version,
            'served_at' => $ticket->served_at?->toIso8601String(),
        ];

        $currentCall = $activeTickets->firstWhere('is_calling', true);
        $recentCalls = $session->tickets()
            ->with(['barber:id,name', 'service:id,name'])
            ->whereIn('status', ['in_queue', 'serving', 'completed'])
            ->whereNotNull('called_at')
            ->orderByDesc('called_at')
            ->limit(10)
            ->get()
            ->reject(fn (QueueTicket $ticket) => $currentCall && $ticket->id === $currentCall->id)
            ->take(3)
            ->values();

        return response()->json([
            'queue_active' => true,
            'current_call' => $currentCall ? $mapTicket($currentCall) : null,
            'recent_calls' => $recentCalls->map($mapTicket),
            'upcoming' => $activeTickets->where('status', 'in_queue')->take(8)->values()->map($mapTicket),
            'waiting_count' => $activeTickets->where('status', 'in_queue')->count(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /** Announce a waiting ticket without changing its queue status. */
    public function call(QueueTicket $ticket): JsonResponse
    {
        if (!Barber::where('is_active', true)->exists()) {
            return response()->json(['message' => 'The queue is paused because no barbers are active.'], 409);
        }

        if ($ticket->status !== 'in_queue' || !$ticket->session->session_date->isToday()) {
            return response()->json(['message' => 'Only today’s waiting tickets can be called.'], 422);
        }

        DB::transaction(function () use ($ticket) {
            $ticket->session->tickets()
                ->whereIn('status', ['in_queue', 'serving'])
                ->where('is_calling', true)
                ->update(['is_calling' => false]);

            $ticket->increment('call_version', 1, [
                'is_calling' => true,
                'called_at' => now(),
            ]);
        });

        return response()->json($ticket->fresh(['barber', 'service']));
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
        if (!Barber::where('is_active', true)->exists()) {
            return response()->json(['message' => 'The queue is closed because no barbers are active. Please try again later.'], 409);
        }

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
        if ($request->status === 'serving' && !Barber::where('is_active', true)->exists()) {
            return response()->json(['message' => 'The queue is paused because no barbers are active.'], 409);
        }

        if ($request->status === 'canceled') {
            $updated = QueueTicket::query()
                ->whereKey($ticket->getKey())
                ->whereIn('status', ['in_queue', 'serving'])
                ->update([
                    'status' => 'canceled',
                    'is_calling' => false,
                    'finished_at' => $ticket->finished_at ?? now(),
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                return response()->json(['message' => 'This ticket is no longer active.'], 409);
            }

            return response()->json($ticket->fresh());
        }

        $data = ['status' => $request->status];
        $wasNotCompleted = $ticket->status !== 'completed';

        if ($request->status === 'serving' && !$ticket->served_at) {
            $data['served_at'] = now();
        }

        if (in_array($request->status, ['serving', 'completed', 'canceled'])) {
            $data['is_calling'] = false;
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
        $ahead = null;
        if (in_array($ticket->status, ['in_queue', 'serving'])) {
            $ahead = $session->tickets()
                ->whereIn('status', ['in_queue', 'serving'])
                ->where('queue_number', '<', $ticket->queue_number)
                ->count();
            $position = $ahead + 1;
        }

        $avgServiceMinutes = (float) $session->tickets()
            ->where('status', 'completed')
            ->whereNotNull('served_at')
            ->whereNotNull('finished_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, served_at, finished_at)) as average')
            ->value('average');
        if ($avgServiceMinutes <= 0) {
            $avgServiceMinutes = (float) (Service::where('is_active', true)->avg('duration_minutes') ?? 0);
        }
        $activeBarbers = max(Barber::where('is_active', true)->count(), 1);
        $estimatedWait = $ahead !== null && $avgServiceMinutes > 0
            ? (int) ceil(($ahead * $avgServiceMinutes) / $activeBarbers)
            : null;

        return response()->json([
            'ticket' => $ticket,
            'position' => $position,
            'ahead' => $ahead,
            'estimated_wait_minutes' => $estimatedWait,
        ]);
    }

    /**
     * Sends an SMS via a template and logs the attempt (success or failure)
     * to sms_logs, so every automated and manual send is auditable.
     */
    private function sendAndLogSms(QueueTicket $ticket, int $templateId, array $variables): ?array
{
    $template = MessageTemplate::find($templateId);

    if (!$template || !$template->is_active) {
        $ticket->smsLogs()->create([
            'message_template_id' => $templateId,
            'phone' => $ticket->customer_phone,
            'message_body' => '',
            'status' => 'skipped',
            'sent_at' => null,
        ]);

        \Log::info("SMS skipped — template {$templateId} is inactive or missing.");
        return null;
    }

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
