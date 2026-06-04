<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAttachments
{
    /**
     * Relación polimórfica: todos los adjuntos de este modelo.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Devuelve el Project padre.
     *
     * Contrato implícito: los modelos que usen este trait deben tener
     *   - project_id  (Task, Ticket, Blocker)
     *   - o ser ellos mismos Project
     *
     * @return \App\Models\Project|null
     */
    public function resolveProject(): ?\App\Models\Project
    {
        if ($this instanceof \App\Models\Project) {
            return $this;
        }

        if (method_exists($this, 'project')) {
            return $this->project;
        }

        return null;
    }

    /**
     * UUID del proyecto raíz para construir el disk_path aislado.
     */
    public function getProjectUuid(): ?string
    {
        $project = $this->resolveProject();

        if (!$project) {
            return null;
        }

        if (empty($project->uuid)) {
            $project->uuid = (string) \Illuminate\Support\Str::uuid();
            $project->save();
        }

        return $project->uuid;
    }
}
