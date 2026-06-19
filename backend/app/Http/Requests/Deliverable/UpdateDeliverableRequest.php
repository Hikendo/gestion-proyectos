<?php

namespace App\Http\Requests\Deliverable;

use App\Models\Deliverable;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDeliverableRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $this->user()->canForProject($project, 'deliverable.edit');
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'delivery_date' => ['nullable', 'date'],
            'phase_id'      => ['nullable', 'exists:project_phases,id'],
            'parent_id'     => ['nullable', 'exists:deliverables,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $parentId = $this->input('parent_id');
                if (! $parentId) {
                    return;
                }

                $deliverable = $this->route('deliverable');
                $ownId       = $deliverable instanceof Deliverable ? $deliverable->id : null;

                // Detectar auto-referencia
                if ($ownId !== null && (int) $parentId === $ownId) {
                    $validator->errors()->add('parent_id', 'Un entregable no puede ser su propio padre.');
                    return;
                }

                // Detectar ciclos: recorrer la cadena de parent_id
                $visited = $ownId !== null ? [$ownId] : [];
                $visited[] = $parentId;
                $current = Deliverable::find($parentId);

                while ($current && $current->parent_id) {
                    if (in_array($current->parent_id, $visited, true)) {
                        $validator->errors()->add(
                            'parent_id',
                            'Se detectó una dependencia circular. El entregable padre seleccionado genera un ciclo.'
                        );
                        return;
                    }
                    $visited[] = $current->parent_id;
                    $current = Deliverable::find($current->parent_id);
                }
            },
        ];
    }
}
