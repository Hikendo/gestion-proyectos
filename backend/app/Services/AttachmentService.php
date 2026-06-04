<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Gestiona la subida polimórfica de archivos adjuntos con aislamiento físico
 * por proyecto: todos los archivos se almacenan en:
 *   projects/{project_uuid}/{attachment_uuid}.{ext}
 */
class AttachmentService
{
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
     * Sube un único archivo.
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
        ]);

        $attachable->attachments()->save($attachment);

        return $attachment;
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
