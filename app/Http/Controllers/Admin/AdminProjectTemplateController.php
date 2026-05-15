<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectTemplate;
use App\Models\Construction\ProjectTemplatePhase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProjectTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => ProjectTemplate::with('phases')->orderBy('sort_order')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'project_type' => 'required|string',
            'tipologia' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $template = ProjectTemplate::create($data);

        return response()->json(['data' => $template], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = ProjectTemplate::findOrFail($id);
        $template->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'project_type' => 'sometimes|string',
            'tipologia' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]));

        return response()->json(['data' => $template->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        ProjectTemplate::findOrFail($id)->delete();

        return response()->json(['message' => 'Removido.']);
    }

    public function storePhase(Request $request, int $id): JsonResponse
    {
        $template = ProjectTemplate::findOrFail($id);
        $phase = $template->phases()->create($request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_position' => 'integer',
            'estimated_days' => 'nullable|integer',
            'is_required' => 'boolean',
        ]));

        return response()->json(['data' => $phase], 201);
    }

    public function destroyPhase(int $id, int $phaseId): JsonResponse
    {
        ProjectTemplatePhase::where('project_template_id', $id)->where('id', $phaseId)->delete();

        return response()->json(['message' => 'Fase removida.']);
    }
}
