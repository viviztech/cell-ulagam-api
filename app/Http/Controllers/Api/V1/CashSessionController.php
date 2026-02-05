<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CashSessionResource;
use App\Models\CashSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CashSessionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CashSession::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return CashSessionResource::collection($query->latest()->paginate($request->get('per_page', 20)));
    }

    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $existing = CashSession::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'You already have an open cash session'], 422);
        }

        $session = CashSession::create([
            'shop_id' => $request->user()->shop_id ?? app('current_shop_id'),
            'user_id' => $request->user()->id,
            'opening_balance' => $validated['opening_balance'],
            'status' => 'open',
            'opened_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Cash session opened',
            'cash_session' => new CashSessionResource($session->load('user')),
        ], 201);
    }

    public function current(Request $request): JsonResponse
    {
        $session = CashSession::with(['user', 'movements'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$session) {
            return response()->json(['message' => 'No open cash session', 'cash_session' => null]);
        }

        return response()->json(['cash_session' => new CashSessionResource($session)]);
    }

    public function show(CashSession $cashSession): CashSessionResource
    {
        return new CashSessionResource($cashSession->load(['user', 'movements']));
    }

    public function close(Request $request, CashSession $cashSession): JsonResponse
    {
        if ($cashSession->status !== 'open') {
            return response()->json(['message' => 'Session is already closed'], 422);
        }

        if ($cashSession->user_id !== $request->user()->id && !$request->user()->isSuperAdmin() && !$request->user()->isShopAdmin()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $expectedBalance = (float) $cashSession->opening_balance
            + (float) $cashSession->cash_in_total
            - (float) $cashSession->cash_out_total;

        $cashSession->update([
            'closing_balance' => $validated['closing_balance'],
            'expected_balance' => $expectedBalance,
            'difference' => $validated['closing_balance'] - $expectedBalance,
            'status' => 'closed',
            'closed_at' => now(),
            'notes' => $validated['notes'] ?? $cashSession->notes,
        ]);

        return response()->json([
            'message' => 'Cash session closed',
            'cash_session' => new CashSessionResource($cashSession->load(['user', 'movements'])),
        ]);
    }
}
