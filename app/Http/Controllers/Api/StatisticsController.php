<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\QueueSession;
use App\Models\QueueTicket;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    /**
     * Summary metric cards: customers today, avg wait, avg service, completion rate,
     * each with a percentage change versus yesterday.
     */
    public function summary(): JsonResponse
    {
        $today = $this->calcDayStats(today());
        $yesterday = $this->calcDayStats(today()->subDay());

        return response()->json([
            'customers_today' => $today['total'],
            'avg_wait_minutes' => $today['avg_wait'],
            'avg_service_minutes' => $today['avg_service'],
            'completion_rate' => $today['completion_rate'],
            'customers_change_pct' => $this->pctChange($yesterday['total'], $today['total']),
            'avg_wait_change_pct' => $this->pctChange($yesterday['avg_wait'], $today['avg_wait']),
            'avg_service_change_pct' => $this->pctChange($yesterday['avg_service'], $today['avg_service']),
            'completion_rate_change_pct' => $this->pctChange($yesterday['completion_rate'], $today['completion_rate']),
        ]);
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
        $hours = range(9, 20);

        $counts = QueueTicket::join('queue_sessions', 'queue_tickets.queue_session_id', '=', 'queue_sessions.id')
            ->whereBetween('queue_sessions.session_date', [$from, $to])
            ->whereRaw('HOUR(queue_tickets.joined_at) between 9 and 20')
            ->selectRaw('HOUR(queue_tickets.joined_at) as hour, count(*) as total')
            ->groupBy('hour')
            ->pluck('total', 'hour');

        $waits = QueueTicket::join('queue_sessions', 'queue_tickets.queue_session_id', '=', 'queue_sessions.id')
            ->whereBetween('queue_sessions.session_date', [$from, $to])
            ->whereNotNull('queue_tickets.served_at')
            ->whereRaw('HOUR(queue_tickets.joined_at) between 9 and 20')
            ->selectRaw('HOUR(queue_tickets.joined_at) as hour, AVG(TIMESTAMPDIFF(MINUTE, queue_tickets.joined_at, queue_tickets.served_at)) as avg_wait')
            ->groupBy('hour')
            ->pluck('avg_wait', 'hour');

        $categories = [];
        $overview = [];
        $waitTimes = [];

        foreach ($hours as $h) {
            $categories[] = Carbon::createFromTime($h)->format('gA');
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
    public function barberPerformance(): JsonResponse
    {
        $data = Barber::withCount(['queueTickets' => function ($q) {
                $q->where('status', 'completed')
                  ->whereHas('session', function ($s) {
                      $s->whereDate('session_date', today());
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