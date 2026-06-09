<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Gestiona la subida polimórfica de archivos adjuntos con aislamiento físico
 * por proyecto: todos los archivos se almacenan en:
 *   projects/{project_uuid}/{attachment_uuid}.{ext}
 *
 * Soporta un ciclo de vida temporal:
 *   1. uploadTemporary() → guarda en drafts/{uuid}.{ext} (status=temp)
 *   2. claim()           → mueve a projects/{project_uuid}/{uuid}.{ext} (status=claimed)
 */
class AttachmentService
{
    /**
     * Directorio raíz para archivos temporales (no asociados a un proyecto todavía).
     */
    private const DRAFT_ROOT = 'drafts';

    /**
     * Sube múltiples archivos y los asocia al modelo polimórfico dado.
     *
     * @param  Model                     $attachable Instancia del modelo padre (Project, Task, Ticket, Blocker)
     * @param  array<int, UploadedFile>  $files
     * @param  User                      $uploader
     * @return array<int, Attachment>
     */
    public function uploadMany(Model $attachable, array $files, User $uploader): array
    {
        $attachments = [];

        foreach ($files as $file) {
            $attachments[] = $this->upload($attachable, $file, $uploader);
        }

        return $attachments;
    }

    /**
     * Sube un único archivo asociado directamente a un modelo padre.
     */
    public function upload(Model $attachable, UploadedFile $file, User $uploader): Attachment
    {
        $projectUuid = $this->resolveProjectUuid($attachable);
        $attachmentUuid = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension();

        // Construir nombre aislado: projects/{project_uuid}/{attachment_uuid}.ext
        $diskPath = sprintf('projects/%s/%s.%s', $projectUuid, $attachmentUuid, $extension);

        // Asegurar que el directorio existe
        Storage::disk('local')->makeDirectory(dirname($diskPath));

        // Almacenar físicamente
        Storage::disk('local')->putFileAs(
            dirname($diskPath),
            $file,
            basename($diskPath)
        );

        // Persistir en BD
        $attachment = new Attachment([
            'uuid'           => $attachmentUuid,
            'original_name'  => $file->getClientOriginalName(),
            'disk_path'      => $diskPath,
            'mime_type'      => $file->getMimeType(),
            'size'           => $file->getSize(),
            'uploaded_by'    => $uploader->id,
            'status'         => 'claimed',
        ]);

        $attachable->attachments()->save($attachment);

        return $attachment;
    }

    /**
     * Sube un archivo temporal sin asociar a ningún padre todavía.
     * El archivo se almacena en drafts/{uuid}.{ext} con status='temp'.
     *
     * @return Attachment
     */
    public function uploadTemporary(UploadedFile $file, User $uploader): Attachment
    {
        $uuid = (string) Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $diskPath = sprintf('%s/%s.%s', self::DRAFT_ROOT, $uuid, $extension);

        // Asegurar que el directorio drafts existe
        Storage::disk('local')->makeDirectory(self::DRAFT_ROOT);

        Storage::disk('local')->putFileAs(
            self::DRAFT_ROOT,
            $file,
            basename($diskPath)
        );

        return Attachment::create([
            'uuid'           => $uuid,
            'original_name'  => $file->getClientOriginalName(),
            'disk_path'      => $diskPath,
            'mime_type'      => $file->getMimeType(),
            'size'           => $file->getSize(),
            'uploaded_by'    => $uploader->id,
            'status'         => 'temp',
        ]);
    }

    /**
     * Claim temporary attachments: associate them with a parent resource and
     * move files from drafts/ to the project's isolated directory.
     *
     * Uses a database transaction and safe path reconstruction.
     *
     * @param  Model                $attachable  Parent model (Project, Task, Ticket, Blocker)
     * @param  array<int, string>   $uuids       UUIDs of temporary attachments
     * @param  User                 $user        The claiming user
     * @return array<int, Attachment>
     *
     * @throws RuntimeException if a temp file is missing from disk.
     */
    public function claim(Model $attachable, array $uuids, User $user): array
    {
        $projectUuid = $this->resolveProjectUuid($attachable);

        // Obtener los attachments temporales del usuario
        $attachments = Attachment::whereIn('uuid', $uuids)
            ->where('status', 'temp')
            ->where('uploaded_by', $user->id)
            ->get();

        if ($attachments->isEmpty()) {
            return [];
        }

        $claimed = [];

        DB::transaction(function () use ($attachable, $attachments, $projectUuid, &$claimed) {
            $disk = Storage::disk('local');
            $destDir = 'projects/' . $projectUuid;
            $disk->makeDirectory($destDir);

            foreach ($attachments as $attachment) {
                // --- Safe path reconstruction ---
                // Extract just the relative filename from the draft path
                // e.g., "drafts/abc-123.pdf" → "abc-123.pdf"
                $relativeFilename = Str::afterLast($attachment->disk_path, '/');

                // Determine source disk path
                $sourceDiskPath = $attachment->disk_path;

                // If the source is not found at the recorded path, fallback to drafts root
                if (!$disk->exists($sourceDiskPath)) {
                    $sourceDiskPath = self::DRAFT_ROOT . '/' . $relativeFilename;
                }

                if (!$disk->exists($sourceDiskPath)) {
                    throw new RuntimeException(
                        "Archivo temporal no encontrado en disco: {$attachment->uuid}"
                    );
                }

                // Destination: projects/{project_uuid}/{original_uuid.ext}
                $newDiskPath = $destDir . '/' . $relativeFilename;

                // Move the file using the Storage facade
                $disk->move($sourceDiskPath, $newDiskPath);

                // Update the attachment record
                $attachment->update([
                    'disk_path'      => $newDiskPath,
                    'status'         => 'claimed',
                    'attachable_type' => $attachable::class,
                    'attachable_id'  => $attachable->id,
                ]);

                $claimed[] = $attachment;
            }
        });

        return $claimed;
    }

    /**
     * Resuelve el UUID del proyecto padre, necesario para el aislamiento físico.
     *
     * @throws RuntimeException si no se puede determinar el proyecto padre.
     */
    private function resolveProjectUuid(Model $attachable): string
    {
        // Si es un Project directamente
        if ($attachable instanceof \App\Models\Project) {
            return $this->ensureProjectUuid($attachable);
        }

        // Si el modelo tiene trait HasAttachments con getProjectUuid()
        if (method_exists($attachable, 'getProjectUuid')) {
            $uuid = $attachable->getProjectUuid();
            if ($uuid) {
                return $uuid;
            }
        }

        throw new RuntimeException(
            'No se pudo determinar el proyecto padre para el modelo ' . $attachable::class
        );
    }

    /**
     * Asegura que un modelo Project tenga UUID, generándolo si falta.
     */
    public function ensureProjectUuid(\App\Models\Project $project): string
    {
        if (empty($project->uuid)) {
            $project->uuid = (string) Str::uuid();
            $project->save();
        }

        return $project->uuid;
    }

    /**
     * Elimina un adjunto de la BD y del disco.
     */
    public function delete(Attachment $attachment): void
    {
        Storage::disk('local')->delete($attachment->disk_path);
        $attachment->delete();
    }

    /**
     * Elimina todos los archivos físicos de un proyecto completo.
     */
    public function deleteProjectDirectory(string $projectUuid): void
    {
        $dir = 'projects/' . $projectUuid;
        if (Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->deleteDirectory($dir);
        }
    }
}
