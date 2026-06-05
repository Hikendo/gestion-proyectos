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

        return Storage::disk('local')->download(
            $attachment->disk_path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream']
        );
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
