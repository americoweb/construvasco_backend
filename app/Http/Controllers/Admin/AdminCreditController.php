<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Credits\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCreditController extends Controller
{
    public function __construct(private CreditService $credits) {}

    public function show(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $balance = $this->credits->getBalance($user);
        $history = $this->credits->getHistory($user, 100);

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'balance' => $balance,
                'history' => $history,
            ],
        ]);
    }

    public function grant(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1|max:10000',
            'notes' => 'required|string|max:500',
        ]);

        $user = User::findOrFail($id);
        $admin = $request->user();

        $balance = $this->credits->manualGrant(
            $user,
            (int) $validated['amount'],
            $validated['notes'],
            $admin,
        );

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'balance' => $balance->balance,
            ],
        ]);
    }
}
