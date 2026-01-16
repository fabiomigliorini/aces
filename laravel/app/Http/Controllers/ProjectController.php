<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize("viewAny", Project::class);

        // TenantScope já filtra automaticamente pelo tenant atual
        $projects = Project::query()
            ->orderBy("created_at", "desc")
            ->paginate(15);

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize("create", Project::class);

        $validated = $request->validate([
            "name" => "required|string|max:255",
            "description" => "nullable|string",
            "status" => "nullable|string|in:active,inactive,archived",
        ]);

        // organization_id e tenant_id são preenchidos automaticamente pelos traits
        $project = Project::create($validated);

        return response()->json($project, 201);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize("view", $project);

        return response()->json($project);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorize("update", $project);

        $validated = $request->validate([
            "name" => "sometimes|required|string|max:255",
            "description" => "nullable|string",
            "status" => "nullable|string|in:active,inactive,archived",
        ]);

        $project->update($validated);

        return response()->json($project);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize("delete", $project);

        $project->delete();

        return response()->json(null, 204);
    }
}