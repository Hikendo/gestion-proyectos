<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DeliverableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Deliverable\StoreDeliverableRequest;
use App\Http\Requests\Deliverable\UpdateDeliverableRequest;
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

        try {
            $items = $project->deliverables()->orderBy('delivery_date')->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Entregables encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/deliverables
     */
    public function store(StoreDeliverableRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $item = $project->deliverables()->create($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $item,
                'message' => 'Entregable creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/projects/{project}/deliverables/{deliverable}
     */
    public function show(Request $request, Project $project, Deliverable $deliverable): JsonResponse
    {
        $this->assertBelongsToProject($deliverable, $project->id);
        $this->authorize('view', $deliverable);

        return response()->json([
            'status'  => true,
            'items'   => $deliverable,
            'message' => 'Entregable encontrado.',
        ]);
    }

    /**
     * PUT /api/projects/{project}/deliverables/{deliverable}
     */
    public function update(UpdateDeliverableRequest $request, Project $project, Deliverable $deliverable): JsonResponse
    {
        $this->assertBelongsToProject($deliverable, $project->id);

        if ($deliverable->approved) {
            throw DeliverableException::alreadyApproved();
        }

        $this->authorize('update', $deliverable);

        try {
            $deliverable->update($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $deliverable,
                'message' => 'Entregable actualizado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/projects/{project}/deliverables/{deliverable}/approve
     */
    public function approve(Request $request, Project $project, Deliverable $deliverable): JsonResponse
    {
        $this->assertBelongsToProject($deliverable, $project->id);

        if ($deliverable->approved) {
            throw DeliverableException::alreadyApproved();
        }

        $this->authorize('approve', $deliverable);

        try {
            $deliverable->update(['approved' => true]);

            return response()->json([
                'status'  => true,
                'items'   => $deliverable,
                'message' => 'Entregable aprobado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
