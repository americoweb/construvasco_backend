<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectRequest;
use App\Services\Credits\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request, CreditService $credits): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'data' => [
                'credits_balance' => $credits->getBalance($user),
                'project_requests_count' => ProjectRequest::where('user_id', $user->id)->count(),
                'pending_quotes' => ProjectRequest::where('user_id', $user->id)->where('status', 'quoted')->count(),
            ],
        ]);
    }
}
