<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use App\Services\QueueingCalculator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * Summary metrics and queue model for today's session only.
     */
    public function summary(QueueingCalculator $calc): JsonResponse
    {
        $today = $this->calcDayStats(today());
        $queueing = $this->calcQueueingMetrics($calc);

        return response()->json([
            'customers_today' => $today['total'],
            'avg_wait_minutes' => $today['avg_wait'],
            'avg_service_minutes' => $today['avg_service'],
            'completion_rate' => $today['completion_rate'],
            // --- M/M/S queueing model, using active tickets only ---
            'servers_active' => $queueing['servers'],
            'utilization_pct' => $queueing['rho'] !== null ? round($queueing['rho'] * 100, 1) : null,
            'predicted_wait_minutes' => $queueing['Wq'] !== null ? round($queueing['Wq'] * 60, 1) : null,
            'avg_in_queue' => $queueing['Lq'] !== null ? round($queueing['Lq'], 2) : null,
            'avg_in_system' => $queueing['L'] !== null ? round($queueing['L'], 2) : null,
            'queue_model_stable' => $queueing['stable'],
        ]);
    }

    /**
     * Applies the M/M/S formulas. Arrival and service estimates use today's
     * in_queue/serving tickets only; completed/canceled tickets are excluded.
     */
    private function calcQueueingMetrics(QueueingCalculator $calc): array
    {
        $session = QueueSession::whereDate('session_date', today())->first();
        $servers = Barber::where('is_active', true)->count();
        $activeTickets = $session
            ? $session->tickets()->whereIn('status', ['in_queue', 'serving'])->get()
            : collect();
        if (!$session || $activeTickets->isEmpty()) {
            return ['rho' => 0.0, 'Lq' => 0.0, 'L' => 0.0, 'Wq' => 0.0, 'stable' => true, 'servers' => $servers];
        }

        if ($servers === 0) {
            return ['rho' => null, 'Lq' => null, 'L' => null, 'Wq' => null, 'stable' => false, 'servers' => 0];
        }

        // Estimate service duration from completed tickets in today's session.
        $avgServiceMinutes = (float) ($session->tickets()
            ->where('status', 'completed')->whereNotNull('served_at')->whereNotNull('finished_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, served_at, finished_at)) as average')
            ->value('average') ?? 0);

        if ($avgServiceMinutes <= 0) {
            return ['rho' => 0.0, 'Lq' => 0.0, 'L' => 0.0, 'Wq' => 0.0, 'stable' => true, 'servers' => $servers];
        }

        $hoursElapsed = max(($session->opened_at ?? now())->diffInMinutes(now()) / 60, 1 / 60);
        // λ is estimated from active tickets observed since today's session opened.
        $lambda = $activeTickets->count() / $hoursElapsed;
        $mu = 60 / $avgServiceMinutes;
        $result = $calc->mms($lambda, $mu, $servers);

        return $result + ['servers' => $servers];
    }

    private function calcDayStats($date): array
    {
        $session = QueueSession::whereDate('session_date', $date)->first();

        if (!$session) {
            return ['total' => 0, 'avg_wait' => 0, 'avg_service' => 0, 'completion_rate' => 0];
        }

        $tickets = $session->tickets();
        $total = (clone $tickets)->count();
        $completed = (clone $tickets)->where('status', 'completed')->count();

        $avgWait = (clone $tickets)->whereNotNull('served_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, joined_at, served_at)) as avg')
            ->value('avg');

        $avgService = (clone $tickets)->where('status', 'completed')->whereNotNull('finished_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, served_at, finished_at)) as avg')
            ->value('avg');

        return [
            'total' => $total,
            'avg_wait' => round((float) ($avgWait ?? 0), 1),
            'avg_service' => round((float) ($avgService ?? 0), 1),
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Hourly breakdown for the Queue Statistics chart:
     * overview (ticket count), peakHours (same as overview), waitTimes (avg wait per hour).
     * Accepts optional ?from=YYYY-MM-DD&to=YYYY-MM-DD query params.
     */
    public function hourly(Request $request): JsonResponse
{
    $from = $request->query('from', now()->subDays(6)->toDateString());
    $to = $request->query('to', now()->toDateString());
    // The shop operates from 11:00 AM through 10:59 PM. Keep the 10 PM
    // bucket inclusive so every ticket joined during the final open hour is counted.
    $hours = range(11, 22);

    // Match QueueTicketController::history: the chart summarizes the same
    // completed and canceled tickets shown in Queue History.
    $baseQuery = QueueTicket::whereIn('status', ['completed', 'canceled'])
        ->whereHas('session', function ($s) use ($from, $to) {
            $s->whereBetween('session_date', [$from, $to]);
        });

    // Match the local times shown in Queue History. Database timestamps are
    // stored in UTC, while the shop timezone is configured for maintenance.
    $shopTimezone = config('app.maintenance.timezone', config('app.timezone'));
    $hourlyCounts = array_fill_keys($hours, 0);
    $hourlyWaits = array_fill_keys($hours, []);

    (clone $baseQuery)->get(['joined_at', 'served_at'])->each(function ($ticket) use (&$hourlyCounts, &$hourlyWaits, $shopTimezone) {
        $joinedAt = $ticket->joined_at->copy()->timezone($shopTimezone);
        $hour = (int) $joinedAt->format('G');

        if (!array_key_exists($hour, $hourlyCounts)) {
            return;
        }

        $hourlyCounts[$hour]++;

        if ($ticket->served_at) {
            $servedAt = $ticket->served_at->copy()->timezone($shopTimezone);
            $hourlyWaits[$hour][] = $joinedAt->diffInMinutes($servedAt);
        }
    });

    $categories = [];
    $overview = [];
    $waitTimes = [];

    foreach ($hours as $h) {
        $categories[] = \Carbon\Carbon::createFromTime($h)->format('gA');
        $overview[] = $hourlyCounts[$h];
        $waitTimes[] = $hourlyWaits[$h]
            ? round(array_sum($hourlyWaits[$h]) / count($hourlyWaits[$h]), 1)
            : 0;
    }

    return response()->json([
        'categories' => $categories,
        'overview' => $overview,
        'peakHours' => $overview,
        'waitTimes' => $waitTimes,
    ]);
}
    /**
     * Monthly report: completed ticket count per month for the current year.
     */
    public function monthlyReport(): JsonResponse
    {
        $data = QueueTicket::join('queue_sessions', 'queue_tickets.queue_session_id', '=', 'queue_sessions.id')
            ->where('queue_tickets.status', 'completed')
            ->whereYear('queue_sessions.session_date', now()->year)
            ->selectRaw('MONTH(queue_sessions.session_date) as month, count(*) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }

    /**
     * Barber performance: completed ticket count per barber, today only.
     */
    public function barberPerformance(Request $request): JsonResponse
{
    $from = $request->query('from', today()->toDateString());
    $to = $request->query('to', today()->toDateString());

    $data = Barber::withCount(['queueTickets' => function ($q) use ($from, $to) {
            $q->where('status', 'completed')
              ->whereHas('session', function ($s) use ($from, $to) {
                  $s->whereBetween('session_date', [$from, $to]);
              });
        }])
        ->get(['id', 'name'])
        ->map(function ($barber) {
            return [
                'name' => $barber->name,
                'completed' => $barber->queue_tickets_count,
            ];
        });

    return response()->json($data);
}
}
