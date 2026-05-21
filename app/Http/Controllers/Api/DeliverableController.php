<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DeliverableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Deliverable\StoreDeliverableRequest;
use App\Http\Requests\Deliverable\UpdateDeliverableRequest;
use App\Http\Resources\DeliverableResource;
use App\Models\Deliverable;
use App\Models\Project;
use App\Traits\BelongsToProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliverableController extends Controller
{
    use BelongsToProject;

    /**
     * GET /api/projects/{project}/deliverables
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return DeliverableResource::collection(
            $project->deliverables()->orderBy('delivery_date')->get()
        )->response();
    }

    /**
     * POST /api/projects/{project}/deliverables
     */
    public function store(StoreDeliverableRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $deliverable = $project->deliverables()->create($request->validated());

        return DeliverableResource::make($deliverable)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/projects/{project}/deliverables/{deliverable}
     */
    public function update(UpdateDeliverableRequest $request, Project $project, Deliverable $deliverable): JsonResponse
    {
        $this->assertBelongsToProject($deliverable, $project->id);

        if ($deliverable->approved) {                    // ← mover ANTES del authorize
            throw DeliverableException::alreadyApproved();
        }

        $this->authorize('update', $deliverable);

        $deliverable->update($request->validated());

        return DeliverableResource::make($deliverable)->response();
    }

    /**
     * PATCH /api/projects/{project}/deliverables/{deliverable}/approve
     */
    public function approve(Request $request, Project $project, Deliverable $deliverable): JsonResponse
    {
        $this->assertBelongsToProject($deliverable, $project->id);

        if ($deliverable->approved) {                    // ← mover ANTES del authorize
            throw DeliverableException::alreadyApproved();
        }

        $this->authorize('approve', $deliverable);

        $deliverable->update(['approved' => true]);

        return response()->json(['message' => 'Entregable aprobado.']);
    }
}
