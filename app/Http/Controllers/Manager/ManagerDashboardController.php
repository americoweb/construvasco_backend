<?php

namespace App\Http\Controllers\Manager;

use App\Enums\ProjectRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class ManagerDashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'pending_requests' => ProjectRequest::where('status', ProjectRequestStatus::Submitted)->count(),
                'under_review' => ProjectRequest::where('status', ProjectRequestStatus::UnderReview)->count(),
                'active_projects' => Project::where('status', 'active')->count(),
            ],
        ]);
    }
}
