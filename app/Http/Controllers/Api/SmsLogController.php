<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = SmsLog::with(['ticket', 'template'])
            ->when($request->from && $request->to, fn ($q) => $q->whereBetween(
                'created_at',
                [$request->from . ' 00:00:00', $request->to . ' 23:59:59']
            ))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest('created_at')
            ->paginate(20);

        return response()->json($logs);
    }
}