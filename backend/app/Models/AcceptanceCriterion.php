<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcceptanceCriterion extends Model
{
    use HasFactory;

    protected $table = 'acceptance_criteria';

    protected $fillable = [
        'phase_id',
        'description',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];

    public function phase()
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    /**
     * Acceso rápido al proyecto a través de la fase.
     */
    public function project()
    {
        return $this->phase->project();
    }
}
