<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Http\Requests\StoreMessageTemplateRequest;
use App\Http\Requests\UpdateMessageTemplateRequest;
use Illuminate\Http\JsonResponse;

class MessageTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(MessageTemplate::orderBy('name')->get());
    }

    public function store(StoreMessageTemplateRequest $request): JsonResponse
    {
        return response()->json(MessageTemplate::create($request->validated()), 201);
    }

    public function update(UpdateMessageTemplateRequest $request, MessageTemplate $messageTemplate): JsonResponse
    {
        $messageTemplate->update($request->validated());
        return response()->json($messageTemplate);
    }

    public function toggleActive(MessageTemplate $messageTemplate): JsonResponse
    {
        $messageTemplate->update(['is_active' => !$messageTemplate->is_active]);
        return response()->json($messageTemplate);
    }

    public function destroy(MessageTemplate $messageTemplate): JsonResponse
    {
        $messageTemplate->delete();
        return response()->json(null, 204);
    }
}