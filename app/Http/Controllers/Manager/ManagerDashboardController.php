<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ProjectRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectPaymentResource;
use App\Models\Construction\ProjectRequest;
use App\Models\Project;
use App\Services\Construction\ConstructionFlowService;
use App\Services\Construction\PaymentService;
use Illuminate\Http\JsonResponse;

class ManagerDashboardController extends Controller
{
    public function __invoke(PaymentService $payments, ConstructionFlowService $construction): JsonResponse
    {
        $pending = $payments->listPendingForManager()->take(5);
        $constructionQuoteRequests = $construction->listPendingConstructionQuoteRequests(5);

        return response()->json([
            'data' => [
                'pending_requests' => ProjectRequest::where('status', ProjectRequestStatus::Submitted)->count(),
                'under_review' => ProjectRequest::where('status', ProjectRequestStatus::UnderReview)->count(),
                'active_projects' => Project::where('status', 'active')->count(),
                'pending_payments_count' => $payments->pendingCount(),
                'pending_payments' => ProjectPaymentResource::collection($pending),
                'pending_construction_quote_requests_count' => $construction->countPendingConstructionQuoteRequests(),
                'pending_construction_quote_requests' => $constructionQuoteRequests->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'client' => $p->client ? ['name' => $p->client->name] : null,
                    'suggested_visit_date' => $p->suggested_site_visit_date?->format('Y-m-d'),
                    'construction_quote_requested_at' => $p->construction_quote_requested_at,
                    'construction_request_notes' => $p->construction_request_notes,
                ]),
            ],
        ]);
    }
}
