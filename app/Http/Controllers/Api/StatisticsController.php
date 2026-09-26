<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Services\QueueingCalculator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * Summary metric cards: customers today, avg wait, avg service, completion rate,
     * each with a percentage change versus yesterday.
     */
    public function summary(QueueingCalculator $calc): JsonResponse
    {
        $today = $this->calcDayStats(today());
        $yesterday = $this->calcDayStats(today()->subDay());
        $queueing = $this->calcQueueingMetrics($today, $calc);

        return response()->json([
            'customers_today' => $today['total'],
            'avg_wait_minutes' => $today['avg_wait'],
            'avg_service_minutes' => $today['avg_service'],
            'completion_rate' => $today['completion_rate'],
            'customers_change_pct' => $this->pctChange($yesterday['total'], $today['total']),
            'avg_wait_change_pct' => $this->pctChange($yesterday['avg_wait'], $today['avg_wait']),
            'avg_service_change_pct' => $this->pctChange($yesterday['avg_service'], $today['avg_service']),
            'completion_rate_change_pct' => $this->pctChange($yesterday['completion_rate'], $today['completion_rate']),

            // --- Queueing theory (M/M/S) model, computed from today's session ---
            'servers_active' => $queueing['servers'],
            'utilization_pct' => $queueing['rho'] !== null ? round($queueing['rho'] * 100, 1) : null,
            'predicted_wait_minutes' => $queueing['Wq'] !== null ? round($queueing['Wq'] * 60, 1) : null,
            'avg_in_queue' => $queueing['Lq'] !== null ? round($queueing['Lq'], 2) : null,
            'avg_in_system' => $queueing['L'] !== null ? round($queueing['L'], 2) : null,
            'queue_model_stable' => $queueing['stable'],
        ]);
    }

    /**
     * Derives λ (arrivals/hour), μ (service rate per barber/hour) and S (active
     * barbers) from today's session, then runs the M/M/S model. This is the
     * theory-backed replacement for the flat "waiting * 15 minutes" guess used
     * in QueueTicketController::estimateWait().
     */
    private function calcQueueingMetrics(array $todayStats, QueueingCalculator $calc): array
    {
        $session = QueueSession::whereDate('session_date', today())->first();
        $servers = max(Barber::where('is_active', true)->count(), 1);

        if (!$session || $todayStats['total'] === 0) {
            return ['rho' => 0.0, 'Lq' => 0.0, 'L' => 0.0, 'Wq' => 0.0, 'stable' => true, 'servers' => $servers];
        }

        // Hours the session has been open (avoid divide-by-zero right at open).
        $hoursElapsed = max(($session->opened_at ?? now())->diffInMinutes(now()) / 60, 1 / 60);
        $lambda = $todayStats['total'] / $hoursElapsed;

        // avg_service is in minutes (completed tickets today) -> convert to a per-hour rate.
        // Early in the day (or with a thin sample) this can be 0, which would make μ
        // uncomputable — fall back to the shop's configured Service.duration_minutes
        // so the model still has a usable estimate instead of going to all-zeros.
        $avgServiceMinutes = $todayStats['avg_service'] > 0
            ? $todayStats['avg_service']
            : (Service::where('is_active', true)->avg('duration_minutes') ?? 0);

        $mu = $avgServiceMinutes > 0 ? 60 / $avgServiceMinutes : 0.0;

        $result = ($lambda > 0 && $mu > 0)
            ? $calc->mms($lambda, $mu, $servers)
            : ['rho' => 0.0, 'Lq' => 0.0, 'L' => 0.0, 'Wq' => 0.0, 'stable' => true];

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

    private function pctChange($old, $new): float
    {
        if ($old == 0) {
            return $new > 0 ? 100.0 : 0.0;
        }
        return round((($new - $old) / $old) * 100, 1);
    }

    /**
     * Hourly breakdown (9AM-8PM) for the Queue Statistics chart:
     * overview (ticket count), peakHours (same as overview), waitTimes (avg wait per hour).
     * Accepts optional ?from=YYYY-MM-DD&to=YYYY-MM-DD query params.
     */
    public function hourly(Request $request): JsonResponse
{
    $from = $request->query('from', now()->subDays(6)->toDateString());
    $to = $request->query('to', now()->toDateString());
    $hours = range(11, 22);

    $baseQuery = QueueTicket::whereIn('status', ['completed', 'canceled'])
        ->whereHas('session', function ($s) use ($from, $to) {
            $s->whereBetween('session_date', [$from, $to]);
        });

    $counts = (clone $baseQuery)
        ->whereRaw('HOUR(joined_at) between 11 and 22')
        ->selectRaw('HOUR(joined_at) as hour, count(*) as total')
        ->groupBy('hour')
        ->pluck('total', 'hour');

    $waits = (clone $baseQuery)
        ->whereNotNull('served_at')
        ->whereRaw('HOUR(joined_at) between 11 and 22')
        ->selectRaw('HOUR(joined_at) as hour, AVG(TIMESTAMPDIFF(MINUTE, joined_at, served_at)) as avg_wait')
        ->groupBy('hour')
        ->pluck('avg_wait', 'hour');

    $categories = [];
    $overview = [];
    $waitTimes = [];

    foreach ($hours as $h) {
        $categories[] = \Carbon\Carbon::createFromTime($h)->format('gA');
        $overview[] = (int) ($counts[$h] ?? 0);
        $waitTimes[] = round((float) ($waits[$h] ?? 0), 1);
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