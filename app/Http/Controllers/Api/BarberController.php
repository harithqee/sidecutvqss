<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Http\Requests\StoreBarberRequest;
use App\Http\Requests\UpdateBarberRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BarberController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Barber::orderBy('name')->get());
    }

    public function store(StoreBarberRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('barbers', 'public');
        }

        $barber = Barber::create($data + ['is_active' => true]);

        return response()->json($barber, 201);
    }

    public function update(UpdateBarberRequest $request, Barber $barber): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($barber->image) {
                Storage::disk('public')->delete($barber->image);
            }
            $data['image'] = $request->file('image')->store('barbers', 'public');
        }

        $barber->update($data);

        return response()->json($barber);
    }

    public function toggleActive(Barber $barber): JsonResponse
    {
        $barber->update(['is_active' => !$barber->is_active]);

        return response()->json($barber);
    }

    public function destroy(Barber $barber): JsonResponse
    {
        // Guard against deleting a barber with live queue tickets
        if ($barber->queueTickets()->whereIn('status', ['in_queue', 'serving'])->exists()) {
            return response()->json([
                'message' => 'Cannot delete a server with active queue tickets.',
            ], 422);
        }

        $barber->delete();

        return response()->json(null, 204);
    }
}
