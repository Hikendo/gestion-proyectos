<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
    ) {}

    /**
     * GET /api/v1/attachments/download/{uuid}
     *
     * Descarga segura: verifica permisos del usuario sobre el recurso padre
     * antes de liberar el archivo.
     */
    public function download(Request $request, string $uuid): BinaryFileResponse|JsonResponse
    {
        $attachment = Attachment::where('uuid', $uuid)->first();

        if (!$attachment) {
            return response()->json([
                'status'  => false,
                'message' => 'Archivo no encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $parent = $attachment->attachable;

        if (!$parent) {
            return response()->json([
                'status'  => false,
                'message' => 'El recurso padre ya no existe.',
            ], Response::HTTP_GONE);
        }

        // Autorización obligatoria contra la policy del recurso padre
        Gate::authorize('view', $parent);

        // Verificar que el archivo físico existe
        if (!Storage::disk('local')->exists($attachment->disk_path)) {
            return response()->json([
                'status'  => false,
                'message' => 'El archivo físico no se encuentra en el sistema.',
            ], Response::HTTP_NOT_FOUND);
        }

        $absolutePath = Storage::disk('local')->path($attachment->disk_path);

        if (!file_exists($absolutePath)) {
            return response()->json([
                'status'  => false,
                'message' => 'El archivo físico no se encuentra en el sistema.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->download(
            $absolutePath,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream']
        );
    }

    /**
     * POST /api/v1/attachments/upload-temp
     *
     * Sube uno o más archivos de forma temporal (status=temp) sin asociarlos
     * a ningún recurso padre todavía. Devuelve los uuids para usarlos después
     * en el endpoint de claim.
     */
    public function uploadTemporary(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => ['required', 'array'],
            'files.*' => ['file', 'max:102400'],
        ]);

        try {
            $uploaded = [];
            foreach ($request->file('files') as $file) {
                $uploaded[] = $this->attachmentService->uploadTemporary($file, $request->user());
            }

            return response()->json([
                'status'  => true,
                'data'    => $uploaded,
                'message' => count($uploaded) . ' archivo(s) temporal(es) subido(s).',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/attachments/claim
     *
     * Asocia archivos temporales (status=temp) a un recurso padre y los mueve
     * del directorio drafts/ al directorio aislado del proyecto.
     *
     * Body: { parent_type: "tasks", parent_id: 123, uuids: ["uuid1", "uuid2"] }
     */
    public function claim(Request $request): JsonResponse
    {
        $request->validate([
            'parent_type' => ['required', 'string', 'in:tasks,tickets,blockers,projects,deliverables'],
            'parent_id'   => ['required', 'integer'],
            'uuids'       => ['required', 'array'],
            'uuids.*'     => ['string', 'uuid'],
        ]);

        try {
            // Resolve the parent model
            $parentClass = match ($request->parent_type) {
                'tasks'        => \App\Models\Task::class,
                'tickets'      => \App\Models\Ticket::class,
                'blockers'     => \App\Models\Blocker::class,
                'projects'     => \App\Models\Project::class,
                'deliverables' => \App\Models\Deliverable::class,
            };

            $parent = $parentClass::findOrFail($request->parent_id);

            // Authorize: user must be able to update the parent
            Gate::authorize('update', $parent);

            $claimed = $this->attachmentService->claim(
                $parent,
                $request->uuids,
                $request->user()
            );

            return response()->json([
                'status'  => true,
                'data'    => $claimed,
                'message' => count($claimed) . ' archivo(s) asociado(s) correctamente.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Recurso padre no encontrado.',
            ], 404);
        } catch (\App\Exceptions\DomainException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/attachments/{uuid}
     *
     * Elimina un adjunto del disco y la base de datos.
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $attachment = Attachment::where('uuid', $uuid)->first();

        if (!$attachment) {
            return response()->json([
                'status'  => false,
                'message' => 'Archivo no encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $parent = $attachment->attachable;

        if (!$parent) {
            return response()->json([
                'status'  => false,
                'message' => 'El recurso padre ya no existe.',
            ], Response::HTTP_GONE);
        }

        // Usar la policy específica de manageAttachments si existe,
        // o fallback a delete del padre.
        if (method_exists(Gate::getPolicyFor($parent::class), 'manageAttachments')) {
            Gate::authorize('manageAttachments', $parent);
        } else {
            Gate::authorize('delete', $parent);
        }

        $this->attachmentService->delete($attachment);

        return response()->json([
            'status'  => true,
            'message' => 'Archivo eliminado correctamente.',
        ]);
    }

    /**
     * POST /api/v1/attachments/{uuid}/replace
     *
     * Reemplaza el archivo físico de un adjunto manteniendo el mismo registro.
     * Útil para actualizar documentos de proyecto.
     */
    public function replace(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'],
        ]);

        $attachment = Attachment::where('uuid', $uuid)->first();

        if (!$attachment) {
            return response()->json([
                'status'  => false,
                'message' => 'Archivo no encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $parent = $attachment->attachable;

        if (!$parent) {
            return response()->json([
                'status'  => false,
                'message' => 'El recurso padre ya no existe.',
            ], Response::HTTP_GONE);
        }

        Gate::authorize('update', $parent);

        /** @var UploadedFile $file */
        $file = $request->file('file');

        // Eliminar archivo físico anterior
        Storage::disk('local')->delete($attachment->disk_path);

        // Subir nuevo archivo manteniendo el uuid (sin cambiar la ruta)
        $dir = dirname($attachment->disk_path);
        $extension = $file->getClientOriginalExtension();
        $newDiskPath = $dir . '/' . $uuid . '.' . $extension;

        Storage::disk('local')->makeDirectory($dir);
        Storage::disk('local')->putFileAs($dir, $file, basename($newDiskPath));

        // Actualizar metadatos
        $attachment->update([
            'disk_path'     => $newDiskPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return response()->json([
            'status' => true,
            'data'   => $attachment->fresh(),
            'message' => 'Archivo reemplazado correctamente.',
        ]);
    }
}
