<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = ProjectAssignment::where('assigned_to', $userId)->where('status', 'active')->count();

        return response()->json(['data' => ['assigned_projects' => $count]]);
    }
}
