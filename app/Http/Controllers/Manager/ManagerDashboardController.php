<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ProjectRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectPaymentResource;
use App\Models\Construction\ProjectRequest;
use App\Models\Project;
use App\Services\Construction\PaymentService;
use Illuminate\Http\JsonResponse;

class ManagerDashboardController extends Controller
{
    public function __invoke(PaymentService $payments): JsonResponse
    {
        $pending = $payments->listPendingForManager()->take(5);

        return response()->json([
            'data' => [
                'pending_requests' => ProjectRequest::where('status', ProjectRequestStatus::Submitted)->count(),
                'under_review' => ProjectRequest::where('status', ProjectRequestStatus::UnderReview)->count(),
                'active_projects' => Project::where('status', 'active')->count(),
                'pending_payments_count' => $payments->pendingCount(),
                'pending_payments' => ProjectPaymentResource::collection($pending),
            ],
        ]);
    }
}
