<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function __construct()
    {
       // $this->middleware(['tenant']);
    }

    public function index(Request $request)
    {
        $projects = Project::query()->get();
        return response()->json(['data' => $projects]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'budget' => 'nullable|numeric',
        ]);
        $project = Project::create($validated + ['tenant_id' => session('tenant_id')]);
        return response()->json(['data' => $project], 201);
    }

    public function show(Project $project)
    {
        if (!$project->belongsToCurrentTenant()) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json(['data' => $project]);
    }

    public function update(Request $request, Project $project)
    {
        if (!$project->belongsToCurrentTenant()) {
            return response()->json(['error' => 'Not found'], 404);
        }
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'budget' => 'nullable|numeric',
        ]);
        $project->update($validated);
        return response()->json(['data' => $project]);
    }

    public function destroy(Project $project)
    {
        if (!$project->belongsToCurrentTenant()) {
            return response()->json(['error' => 'Not found'], 404);
        }
        $project->delete();
        return response()->json(['message' => 'Project deleted']);
    }
} 