<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectPayment;
use App\Models\Construction\ProjectRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class AdminReportController extends Controller
{
    public function financial(): JsonResponse
    {
        return response()->json([
            'data' => [
                'payments_total' => ProjectPayment::where('status', 'paid')->sum('amount'),
                'payments_count' => ProjectPayment::where('status', 'paid')->count(),
            ],
        ]);
    }

    public function operational(): JsonResponse
    {
        return response()->json([
            'data' => [
                'projects_active' => Project::where('status', 'active')->count(),
                'requests_submitted' => ProjectRequest::where('status', 'submitted')->count(),
            ],
        ]);
    }
}
