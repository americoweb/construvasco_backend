<?php



namespace App\Http\Controllers\Technician;



use App\Http\Controllers\Controller;

use App\Models\Construction\ProjectAssignment;

use App\Models\Construction\ProjectDeliverable;

use App\Models\Construction\ProjectMilestone;

use App\Models\Project;

use App\Services\Construction\ProjectFlowService;

use App\Services\Storage\FileStorageService;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;



class TechnicianProjectController extends Controller

{

    public function __construct(

        private ProjectFlowService $flow,

        private FileStorageService $storage

    ) {}



    public function index(Request $request): JsonResponse

    {

        $ids = ProjectAssignment::where('assigned_to', $request->user()->id)

            ->where('status', 'active')

            ->pluck('project_id');



        $projects = Project::whereIn('id', $ids)->with('milestones')->paginate(20);



        return response()->json($projects);

    }



    public function show(Request $request, int $id): JsonResponse

    {

        $this->ensureAssigned($request, $id);

        $project = Project::with(['milestones', 'deliverables'])->findOrFail($id);



        return response()->json(['data' => $project]);

    }



    public function updatePhase(Request $request, int $id, int $phaseId): JsonResponse

    {

        $this->ensureAssigned($request, $id);

        $milestone = ProjectMilestone::where('project_id', $id)->findOrFail($phaseId);

        $milestone->update($request->validate([

            'status' => 'required|in:pending,in_progress,completed,blocked',

            'description' => 'nullable|string',

        ]));



        if ($milestone->status === 'completed') {

            $milestone->update(['completed_at' => now()]);

        }



        return response()->json(['data' => $milestone->fresh()]);

    }



    public function uploadDeliverable(Request $request, int $id, int $phaseId): JsonResponse

    {

        $this->ensureAssigned($request, $id);

        $request->validate([

            'file' => 'required|file|max:51200|mimes:pdf,dwg,dxf,zip,rar,png,jpg,jpeg',

            'deliverable_type' => 'required|string|max:120',

            'title' => 'required|string|max:255',

        ]);



        $file = $request->file('file');

        $this->storage->validateFile($file, [

            'application/pdf',

            'image/png',

            'image/jpeg',

            'application/zip',

            'application/x-rar-compressed',

            'application/octet-stream',

        ], 51200);



        $stored = $this->storage->storePrivateFile($file, "projects/{$id}/deliverables");

        $project = Project::findOrFail($id);



        $deliverable = $this->flow->addDeliverable($project, [

            'project_milestone_id' => $phaseId,

            'deliverable_type' => $request->input('deliverable_type'),

            'title' => $request->input('title'),

            'file_path' => $stored['path'],

            'file_disk' => $stored['disk'],

            'file_format' => $file->getClientOriginalExtension(),

            'mime_type' => $stored['mime'],

            'size_bytes' => $stored['size'],

            'original_name' => $stored['original_name'],

            'uploaded_by' => $request->user()->id,

            'status' => 'submitted',

        ]);



        return response()->json(['data' => $deliverable], 201);

    }



    public function destroyDeliverable(Request $request, int $deliverableId): JsonResponse

    {

        $deliverable = ProjectDeliverable::findOrFail($deliverableId);

        abort_unless($deliverable->uploaded_by === $request->user()->id, 403);



        $this->storage->deleteFile($deliverable->file_path, $deliverable->file_disk ?? 'local');

        $deliverable->delete();



        return response()->json(['message' => 'Removido.']);

    }



    public function submitForReview(Request $request, int $id): JsonResponse

    {

        $this->ensureAssigned($request, $id);

        $project = Project::findOrFail($id);

        $project->update(['status' => 'in_review', 'current_phase' => 'in_review']);



        return response()->json(['data' => $project]);

    }



    private function ensureAssigned(Request $request, int $projectId): void

    {

        $ok = ProjectAssignment::where('project_id', $projectId)

            ->where('assigned_to', $request->user()->id)

            ->where('status', 'active')

            ->exists();

        abort_unless($ok, 403);

    }

}

