<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CashMovementResource;
use App\Models\CashMovement;
use App\Models\CashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashMovementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CashMovement::with('user');

        if ($request->has('cash_session_id')) {
            $query->where('cash_session_id', $request->cash_session_id);
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return CashMovementResource::collection($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_session_id' => 'required|exists:cash_sessions,id',
            'type' => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $session = CashSession::findOrFail($validated['cash_session_id']);

        if (!$session->isOpen()) {
            return response()->json(['message' => 'Cash session is closed'], 422);
        }

        $movement = CashMovement::create([
            'shop_id' => $session->shop_id,
            'cash_session_id' => $session->id,
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
        ]);

        if ($validated['type'] === 'cash_in') {
            $session->increment('cash_in_total', $validated['amount']);
        } else {
            $session->increment('cash_out_total', $validated['amount']);
        }

        return response()->json([
            'message' => 'Cash movement recorded',
            'cash_movement' => new CashMovementResource($movement),
        ], 201);
    }
}
