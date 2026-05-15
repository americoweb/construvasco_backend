<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectAssignment;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Construction\ProjectMilestone;
use App\Models\Construction\ProjectPayment;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'projects_total' => Project::count(),
                'projects_active' => Project::whereNotIn('status', ['completed', 'cancelled'])->count(),
                'milestones_pending' => ProjectMilestone::where('status', 'pending')->count(),
                'deliverables_submitted' => ProjectDeliverable::where('status', 'submitted')->count(),
                'assignments_active' => ProjectAssignment::where('status', 'active')->count(),
                'payments_pending' => ProjectPayment::where('status', 'pending')->count(),
            ],
        ]);
    }
}
